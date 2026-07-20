<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;

final readonly class ExpedienteDocumentoRequerido
{
    public function __construct(
        private ExpedienteDocumentoRequeridoId $id,
        private ExpedienteId $expedienteId,
        private string $nombre,
        private string $descripcion,
        private bool $obligatorio,
        private TipoDocumentoRequerido $tipo,
        private int $maxImagenes,
        private int $orden,
        private OrigenDocumentoRequeridoExpediente $origen,
        private ?TramiteDocumentoRequeridoId $tramiteDocumentoRequeridoId = null,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('now'),
    ) {
    }

    public function id(): ExpedienteDocumentoRequeridoId
    {
        return $this->id;
    }

    public function expedienteId(): ExpedienteId
    {
        return $this->expedienteId;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function obligatorio(): bool
    {
        return $this->obligatorio;
    }

    public function tipo(): TipoDocumentoRequerido
    {
        return $this->tipo;
    }

    public function maxImagenes(): int
    {
        return $this->maxImagenes;
    }

    public function orden(): int
    {
        return $this->orden;
    }

    public function origen(): OrigenDocumentoRequeridoExpediente
    {
        return $this->origen;
    }

    public function tramiteDocumentoRequeridoId(): ?TramiteDocumentoRequeridoId
    {
        return $this->tramiteDocumentoRequeridoId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
