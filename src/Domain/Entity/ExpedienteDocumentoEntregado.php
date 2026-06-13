<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;

final readonly class ExpedienteDocumentoEntregado
{
    public function __construct(
        private string $id,
        private ExpedienteId $expedienteId,
        private TramiteDocumentoRequeridoId $documentoRequeridoId,
        private string $archivoPath,
        private EstadoDocumentoEntregado $estado,
        private \DateTimeImmutable $entregadoAt,
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

    public function documentoRequeridoId(): TramiteDocumentoRequeridoId
    {
        return $this->documentoRequeridoId;
    }

    public function archivoPath(): string
    {
        return $this->archivoPath;
    }

    public function estado(): EstadoDocumentoEntregado
    {
        return $this->estado;
    }

    public function entregadoAt(): \DateTimeImmutable
    {
        return $this->entregadoAt;
    }

    public function marcarValidado(): self
    {
        return new self(
            $this->id,
            $this->expedienteId,
            $this->documentoRequeridoId,
            $this->archivoPath,
            EstadoDocumentoEntregado::Validado,
            $this->entregadoAt,
        );
    }
}
