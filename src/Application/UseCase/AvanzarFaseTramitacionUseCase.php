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

        $progreso = $this->completitudValidator->resumen($id);
        if (!$progreso['requerimientosListo']) {
            throw new \InvalidArgumentException('Aún hay documentos obligatorios pendientes de validar.');
        }

        if (EstadoFaseExpediente::RequerimientosListo !== $expediente->estadoFase()) {
            throw new \InvalidArgumentException('El expediente aún no está listo para pasar a tramitación.');
        }

        $this->expedienteRepository->save(
            $expediente
                ->withFaseNegocio(FaseNegocioExpediente::Tramitacion, EstadoFaseExpediente::Completada)
                ->touchEstadoCambio(),
        );

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'fase_tramitacion_iniciada',
            'El expediente ha pasado a la fase de tramitación.',
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
