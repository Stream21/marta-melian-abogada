<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\GestionesPostResolucionFactory;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteRecordatorioFuturoRepositoryInterface;
use App\Domain\Repository\ExpedienteResolucionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteId;

final class ObtenerResolucionExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteResolucionRepositoryInterface $resolucionRepository,
        private ExpedienteRecordatorioFuturoRepositoryInterface $recordatorioRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private string $frontendBaseUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $expedienteId): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Resolucion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de resolución.');
        }

        $tramite = null;
        if (null !== $expediente->tramiteId() && '' !== $expediente->tramiteId()) {
            $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));
        }

        $resolucion = $this->resolucionRepository->findByExpediente($id);
        $recordatorio = $this->recordatorioRepository->findByExpediente($id);

        $gestiones = null === $resolucion
            ? []
            : GestionesPostResolucionFactory::toResponseArray($resolucion->gestiones());

        $puedeArchivar = null !== $resolucion
            && $expediente->estado()->isOperativo()
            && ($resolucion->gestionesCompletas() || 'denegada' === $resolucion->outcome()->value);

        return [
            'expedienteId' => $expediente->id()->value(),
            'numero' => $expediente->numero(),
            'faseNegocio' => $expediente->faseNegocio()->value,
            'estado' => $expediente->estado()->value,
            'estadoLabel' => $expediente->estado()->label(),
            'tramiteNombre' => $tramite?->nombre(),
            'resolucion' => null === $resolucion ? null : [
                'id' => $resolucion->id()->value(),
                'outcome' => $resolucion->outcome()->value,
                'outcomeLabel' => $resolucion->outcome()->label(),
                'fechaNotificacion' => $resolucion->fechaNotificacion()->format('Y-m-d'),
                'gestiones' => $gestiones,
                'gestionesCompletas' => $resolucion->gestionesCompletas(),
            ],
            'recordatorio' => null === $recordatorio ? null : [
                'id' => $recordatorio->id()->value(),
                'fecha' => $recordatorio->fecha()->format('Y-m-d'),
                'motivo' => $recordatorio->motivo(),
                'servicioId' => $recordatorio->servicioId(),
                'tramiteId' => $recordatorio->tramiteId(),
                'servicioNombre' => $recordatorio->servicioNombre(),
                'tramiteNombre' => $recordatorio->tramiteNombre(),
                'notificado' => null !== $recordatorio->notificadoAt(),
            ],
            'puedeArchivar' => $puedeArchivar,
            'accessUrl' => null !== $expediente->accessToken()
                ? rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken()
                : null,
        ];
    }
}
