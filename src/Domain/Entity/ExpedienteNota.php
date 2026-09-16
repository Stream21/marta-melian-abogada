<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteNotaId;

final readonly class ExpedienteNota
{
    public function __construct(
        private ExpedienteNotaId $id,
        private ExpedienteId $expedienteId,
        private string $contenido,
        private bool $archivada = false,
        private ?\DateTimeImmutable $archivadaAt = null,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('now'),
    ) {
        if ('' === trim($contenido)) {
            throw new \InvalidArgumentException('El contenido de la nota es obligatorio.');
        }
        if (mb_strlen(trim($contenido)) > 5000) {
            throw new \InvalidArgumentException('La nota no puede superar 5000 caracteres.');
        }
    }

    public function id(): ExpedienteNotaId
    {
        return $this->id;
    }

    public function expedienteId(): ExpedienteId
    {
        return $this->expedienteId;
    }

    public function contenido(): string
    {
        return $this->contenido;
    }

    public function archivada(): bool
    {
        return $this->archivada;
    }

    public function archivadaAt(): ?\DateTimeImmutable
    {
        return $this->archivadaAt;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function archivar(\DateTimeImmutable $at = new \DateTimeImmutable('now')): self
    {
        if ($this->archivada) {
            return $this;
        }

        return new self(
            $this->id,
            $this->expedienteId,
            $this->contenido,
            true,
            $at,
            $this->createdAt,
        );
    }

    public function desarchivar(): self
    {
        if (!$this->archivada) {
            return $this;
        }

        return new self(
            $this->id,
            $this->expedienteId,
            $this->contenido,
            false,
            null,
            $this->createdAt,
        );
    }
}
