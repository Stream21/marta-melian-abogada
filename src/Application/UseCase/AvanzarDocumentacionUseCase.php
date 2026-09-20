<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\FechaVencimientoFaseParser;
use App\Application\Service\NotificarCambioFaseClienteService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class AvanzarDocumentacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private InicializarDocumentacionUseCase $inicializarDocumentacion,
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

        if (FaseNegocioExpediente::Contratacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de contratación.');
        }

        $pasos = $this->contratacionRepository->findPasosByExpediente($id);
        foreach ($pasos as $paso) {
            if (EstadoPasoContratacion::ValidadoAbogado !== $paso->estado()) {
                throw new \InvalidArgumentException(
                    'Debe validar todos los pasos de contratación antes de pasar a documentación.',
                );
            }
        }

        if ([] === $pasos) {
            throw new \InvalidArgumentException(
                'Debe validar todos los pasos de contratación antes de pasar a documentación.',
            );
        }

        $fechaLimite = $this->fechaVencimientoParser->parseRequired($fechaVencimientoFase);

        $actualizado = $expediente
            ->withFaseNegocio(FaseNegocioExpediente::Documentacion, EstadoFaseExpediente::DocumentacionEnProgreso)
            ->withFechaVencimientoFase($fechaLimite)
            ->touchEstadoCambio();

        $this->expedienteRepository->save($actualizado);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'fase_completada',
            sprintf(
                'Contratación completada. El expediente pasa a fase de requerimientos (plazo hasta %s).',
                $fechaLimite->format('d/m/Y'),
            ),
            ActorHitoExpediente::Sistema,
            new \DateTimeImmutable('now'),
        ));

        ($this->inicializarDocumentacion)($id);

        $this->notificarCambioFase->notificar($actualizado, FaseNegocioExpediente::Documentacion);

        $this->realtime->publishContratacionUpdate($expedienteId, [
            'type' => 'fase_completada',
            'faseNegocio' => FaseNegocioExpediente::Documentacion->value,
            'fechaVencimientoFase' => $fechaLimite->format('Y-m-d'),
            'actor' => 'sistema',
            'expedienteNumero' => $actualizado->numero(),
            'clienteNombre' => $actualizado->clientName(),
        ]);
    }
}
