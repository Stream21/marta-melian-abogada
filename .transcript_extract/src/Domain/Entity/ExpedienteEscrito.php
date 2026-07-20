<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;

final readonly class ExpedienteEscrito
{
    public function __construct(
        private string $id,
        private ExpedienteId $expedienteId,
        private string $titulo,
        private string $contenidoHtml,
        private string $pdfPath,
        private \DateTimeImmutable $createdAt,
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

    public function titulo(): string
    {
        return $this->titulo;
    }

    public function contenidoHtml(): string
    {
        return $this->contenidoHtml;
    }

    public function pdfPath(): string
    {
        return $this->pdfPath;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
