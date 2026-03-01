<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final readonly class Invoice
{
    public function __construct(
        private string $id,
        private ?string $expedienteId,
        private string $holdedId,
        private string $numero,
        private string $concepto,
        private string $modalidad,
        private \DateTimeImmutable $fecha,
        private float $importe,
        private string $estadoHolded,
        private ?string $pdfPath,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function expedienteId(): ?string
    {
        return $this->expedienteId;
    }

    public function holdedId(): string
    {
        return $this->holdedId;
    }

    public function numero(): string
    {
        return $this->numero;
    }

    public function concepto(): string
    {
        return $this->concepto;
    }

    public function modalidad(): string
    {
        return $this->modalidad;
    }

    public function fecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function importe(): float
    {
        return $this->importe;
    }

    public function estadoHolded(): string
    {
        return $this->estadoHolded;
    }

    public function pdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
