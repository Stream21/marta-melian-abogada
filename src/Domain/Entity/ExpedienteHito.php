<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;

final readonly class ExpedienteHito
{
    public function __construct(
        private string $id,
        private ExpedienteId $expedienteId,
        private string $tipo,
        private string $descripcion,
        private ActorHitoExpediente $actor,
        private \DateTimeImmutable $createdAt,
        private ?PasoContratacionCliente $paso = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function expedienteId(): ExpedienteId
    {
        return $this->expedienteId;
    }

    public function paso(): ?PasoContratacionCliente
    {
        return $this->paso;
    }

    public function tipo(): string
    {
        return $this->tipo;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function actor(): ActorHitoExpediente
    {
        return $this->actor;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
