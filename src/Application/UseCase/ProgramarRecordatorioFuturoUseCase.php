<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\ExpedienteRecordatorioFuturo;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRecordatorioFuturoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\ExpedienteResolucionRepositoryInterface;
use App\Domain\Repository\ServicioRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRecordatorioFuturoId;
use App\Domain\ValueObject\ServicioId;
use App\Domain\ValueObject\TramiteId;

final class ProgramarRecordatorioFuturoUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteResolucionRepositoryInterface $resolucionRepository,
        private ExpedienteRecordatorioFuturoRepositoryInterface $recordatorioRepository,
        private ServicioRepositoryInterface $servicioRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
    ) {
    }

    public function __invoke(
        string $expedienteId,
        string $fechaRaw,
        string $servicioId,
        string $tramiteId,
    ): void {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Resolucion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de resolución.');
        }
        if (null === $this->resolucionRepository->findByExpediente($id)) {
            throw new \InvalidArgumentException('Registre la resolución antes de programar el recordatorio.');
        }

        $servicio = $this->servicioRepository->findById(new ServicioId($servicioId));
        if (null === $servicio || !$servicio->activo()) {
            throw new \InvalidArgumentException('Servicio no válido.');
        }

        $tramite = $this->tramiteRepository->findById(new TramiteId($tramiteId));
        if (null === $tramite || !$tramite->activo()) {
            throw new \InvalidArgumentException('Trámite no válido.');
        }
        if ($tramite->servicioId()->value() !== $servicio->id()->value()) {
            throw new \InvalidArgumentException('El trámite no pertenece al servicio seleccionado.');
        }

        try {
            $fecha = new \DateTimeImmutable($fechaRaw);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Fecha de recordatorio no válida.');
        }

        $hoy = new \DateTimeImmutable('today');
        if ($fecha < $hoy) {
            throw new \InvalidArgumentException('La fecha del recordatorio debe ser hoy o posterior.');
        }

        $existente = $this->recordatorioRepository->findByExpediente($id);
        if (null !== $existente && !$existente->estaPendiente()) {
            throw new \InvalidArgumentException('El recordatorio de este expediente ya fue enviado.');
        }

        $motivo = sprintf('%s — %s', $servicio->nombre(), $tramite->nombre());

        $recordatorio = new ExpedienteRecordatorioFuturo(
            $existente?->id() ?? new ExpedienteRecordatorioFuturoId(bin2hex(random_bytes(16))),
            $id,
            $fecha,
            $motivo,
            $servicio->id()->value(),
            $tramite->id()->value(),
            $servicio->nombre(),
            $tramite->nombre(),
            null,
            $existente?->createdAt() ?? new \DateTimeImmutable('now'),
        );
        $this->recordatorioRepository->save($recordatorio);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'recordatorio_futuro_programado',
            sprintf(
                'Recordatorio programado para %s: %s',
                $fecha->format('d/m/Y'),
                $motivo,
            ),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));
    }
}
