<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\FechaVencimientoFaseParser;
use App\Application\Service\NotificarCambioFaseClienteService;
use App\Application\Service\RequerimientosProgresoCalculator;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\SubfaseTramitacion;
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
        private FechaVencimientoFaseParser $fechaVencimientoParser,
        private NotificarCambioFaseClienteService $notificarCambioFase,
    ) {
    }

    public function __invoke(string $expedienteId, ?string $fechaVencimientoFase): void
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Documentacion !== $expediente->faseNegocio()) {
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

        if (!$progreso['documentacionListo']) {
            throw new \InvalidArgumentException(
                'Debe validar todos los documentos obligatorios antes de pasar a tramitación.',
            );
        }

        $fechaLimite = $this->fechaVencimientoParser->parseRequired($fechaVencimientoFase);

        $actualizado = $expediente
            ->withFaseNegocio(FaseNegocioExpediente::Tramitacion, EstadoFaseExpediente::Completada)
            ->withSubfaseTramitacion(SubfaseTramitacion::PendienteTramitacion)
            ->withFechaVencimientoFase($fechaLimite)
            ->touchEstadoCambio();

        $this->expedienteRepository->save($actualizado);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'fase_tramitacion_iniciada',
            sprintf(
                'Requerimientos completados. El expediente pasa a fase de tramitación (plazo hasta %s).',
                $fechaLimite->format('d/m/Y'),
            ),
            ActorHitoExpediente::Sistema,
            new \DateTimeImmutable('now'),
        ));

        $this->notificarCambioFase->notificar($actualizado, FaseNegocioExpediente::Tramitacion);

        $this->realtime->publishContratacionUpdate($expedienteId, [
            'type' => 'fase_tramitacion_iniciada',
            'faseNegocio' => FaseNegocioExpediente::Tramitacion->value,
            'fechaVencimientoFase' => $fechaLimite->format('Y-m-d'),
            'actor' => 'sistema',
            'expedienteNumero' => $actualizado->numero(),
            'clienteNombre' => $actualizado->clientName(),
        ]);
    }
}
