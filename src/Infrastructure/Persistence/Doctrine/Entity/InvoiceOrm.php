<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice')]
class InvoiceOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 36, nullable: true)]
    private ?string $expedienteId = null;

    #[ORM\Column(type: Types::STRING, length: 24)]
    private string $holdedId;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $numero;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $concepto;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $modalidad;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(type: Types::FLOAT)]
    private float $importe;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $estadoHolded;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getExpedienteId(): ?string
    {
        return $this->expedienteId;
    }

    public function setExpedienteId(?string $expedienteId): void
    {
        $this->expedienteId = $expedienteId;
    }

    public function getHoldedId(): string
    {
        return $this->holdedId;
    }

    public function setHoldedId(string $holdedId): void
    {
        $this->holdedId = $holdedId;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): void
    {
        $this->numero = $numero;
    }

    public function getConcepto(): string
    {
        return $this->concepto;
    }

    public function setConcepto(string $concepto): void
    {
        $this->concepto = $concepto;
    }

    public function getModalidad(): string
    {
        return $this->modalidad;
    }

    public function setModalidad(string $modalidad): void
    {
        $this->modalidad = $modalidad;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeImmutable $fecha): void
    {
        $this->fecha = $fecha;
    }

    public function getImporte(): float
    {
        return $this->importe;
    }

    public function setImporte(float $importe): void
    {
        $this->importe = $importe;
    }

    public function getEstadoHolded(): string
    {
        return $this->estadoHolded;
    }

    public function setEstadoHolded(string $estadoHolded): void
    {
        $this->estadoHolded = $estadoHolded;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): void
    {
        $this->pdfPath = $pdfPath;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
