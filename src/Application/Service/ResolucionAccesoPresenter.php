<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteRecordatorioFuturoRepositoryInterface;
use App\Domain\Repository\ExpedienteResolucionRepositoryInterface;

final class ResolucionAccesoPresenter
{
    public function __construct(
        private ExpedienteResolucionRepositoryInterface $resolucionRepository,
        private ExpedienteRecordatorioFuturoRepositoryInterface $recordatorioRepository,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function present(Expediente $expediente): ?array
    {
        if (FaseNegocioExpediente::Resolucion !== $expediente->faseNegocio()) {
            return null;
        }

        $resolucion = $this->resolucionRepository->findByExpediente($expediente->id());
        $recordatorio = $this->recordatorioRepository->findByExpediente($expediente->id());

        if (null === $resolucion) {
            return [
                'estadoCliente' => 'pendiente_resolucion',
                'estadoClienteLabel' => 'Pendiente de resolución',
                'resolucion' => null,
                'recordatorio' => null,
            ];
        }

        $gestiones = null === $resolucion
            ? []
            : GestionesPostResolucionFactory::toResponseArray($resolucion->gestiones());

        return [
            'estadoCliente' => $resolucion->outcome()->value,
            'estadoClienteLabel' => 'Resolución ' . strtolower($resolucion->outcome()->label()),
            'resolucion' => [
                'outcome' => $resolucion->outcome()->value,
                'outcomeLabel' => $resolucion->outcome()->label(),
                'fechaNotificacion' => $resolucion->fechaNotificacion()->format('Y-m-d'),
                'gestiones' => $gestiones,
            ],
            'recordatorio' => null === $recordatorio ? null : [
                'fecha' => $recordatorio->fecha()->format('Y-m-d'),
                'motivo' => $recordatorio->motivo(),
                'servicioNombre' => $recordatorio->servicioNombre(),
                'tramiteNombre' => $recordatorio->tramiteNombre(),
            ],
        ];
    }
}
