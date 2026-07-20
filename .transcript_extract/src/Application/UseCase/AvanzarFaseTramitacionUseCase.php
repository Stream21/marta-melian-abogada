<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\RequerimientosCompletitudValidator;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class AvanzarFaseTramitacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private RequerimientosCompletitudValidator $completitudValidator,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function __invoke(string $expedienteId): void
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de requerimientos.');
        }

        if (EstadoFaseExpediente::RequerimientosListo !== $expediente->estadoFase()) {
            throw new \InvalidArgumentException('Debe validar todos los documentos obligatorios antes de avanzar.');
        }

        if (!$this->completitudValidator->requerimientosListo($id)) {
            throw new \InvalidArgumentException('Aún hay documentos pendientes de validación o rechazados.');
        }

        $this->expedienteRepository->save(
            $expediente
                ->withFaseNegocio(FaseNegocioExpediente::Tramitacion, EstadoFaseExpediente::PendienteCliente)
                ->touchEstadoCambio(),
        );

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'fase_tramitacion_iniciada',
            'Requerimientos completados. El expediente pasa a fase de tramitación.',
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->realtime->publishContratacionUpdate($id->value(), [
            'type' => 'fase_tramitacion_iniciada',
            'faseNegocio' => FaseNegocioExpediente::Tramitacion->value,
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }
}
