<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRecordatorioFuturoId;

final readonly class ExpedienteRecordatorioFuturo
{
    public function __construct(
        private ExpedienteRecordatorioFuturoId $id,
        private ExpedienteId $expedienteId,
        private \DateTimeImmutable $fecha,
        private string $motivo,
        private ?string $servicioId = null,
        private ?string $tramiteId = null,
        private ?string $servicioNombre = null,
        private ?string $tramiteNombre = null,
        private ?\DateTimeImmutable $notificadoAt = null,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('now'),
    ) {
        if ('' === trim($motivo)) {
            throw new \InvalidArgumentException('El motivo del recordatorio es obligatorio.');
        }
    }

    public function id(): ExpedienteRecordatorioFuturoId
    {
        return $this->id;
    }

    public function expedienteId(): ExpedienteId
    {
        return $this->expedienteId;
    }

    public function fecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function motivo(): string
    {
        return $this->motivo;
    }

    public function servicioId(): ?string
    {
        return $this->servicioId;
    }

    public function tramiteId(): ?string
    {
        return $this->tramiteId;
    }

    public function servicioNombre(): ?string
    {
        return $this->servicioNombre;
    }

    public function tramiteNombre(): ?string
    {
        return $this->tramiteNombre;
    }

    public function notificadoAt(): ?\DateTimeImmutable
    {
        return $this->notificadoAt;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function estaPendiente(): bool
    {
        return null === $this->notificadoAt;
    }

    public function marcarNotificado(\DateTimeImmutable $at = new \DateTimeImmutable('now')): self
    {
        return new self(
            $this->id,
            $this->expedienteId,
            $this->fecha,
            $this->motivo,
            $this->servicioId,
            $this->tramiteId,
            $this->servicioNombre,
            $this->tramiteNombre,
            $at,
            $this->createdAt,
        );
    }
}
