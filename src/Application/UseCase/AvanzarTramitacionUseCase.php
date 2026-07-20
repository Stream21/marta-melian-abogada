<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\RequerimientosProgresoCalculator;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class AvanzarTramitacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private RequerimientosProgresoCalculator $progresoCalculator,
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

        $entregasPorDocId = [];
        foreach ($this->documentoEntregadoRepository->findByExpediente($id) as $entrega) {
            $docId = $entrega->expedienteDocumentoRequeridoId();
            if (null !== $docId) {
                $entregasPorDocId[$docId->value()] = $entrega;
            }
        }

        $documentos = $this->documentoRequeridoRepository->findByExpediente($id);
        $progreso = $this->progresoCalculator->calcular($documentos, $entregasPorDocId);

        if (!$progreso['requerimientosListo']) {
            throw new \InvalidArgumentException(
                'Debe validar todos los documentos obligatorios antes de pasar a tramitación.',
            );
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
            ActorHitoExpediente::Sistema,
            new \DateTimeImmutable('now'),
        ));

        $this->realtime->publishContratacionUpdate($expedienteId, [
            'type' => 'fase_tramitacion_iniciada',
            'faseNegocio' => FaseNegocioExpediente::Tramitacion->value,
            'actor' => 'sistema',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }
}
