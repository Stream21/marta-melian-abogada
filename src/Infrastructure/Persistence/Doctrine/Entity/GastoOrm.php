<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'gasto')]
#[ORM\Index(name: 'idx_gasto_fecha', columns: ['fecha'])]
#[ORM\Index(name: 'idx_gasto_categoria', columns: ['categoria'])]
class GastoOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $concepto;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $importe;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $categoria = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notas = null;

    #[ORM\Column(name: 'factura_pdf_path', type: Types::STRING, length: 500, nullable: true)]
    private ?string $facturaPdfPath = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getConcepto(): string
    {
        return $this->concepto;
    }

    public function setConcepto(string $concepto): void
    {
        $this->concepto = $concepto;
    }

    public function getImporte(): string
    {
        return $this->importe;
    }

    public function setImporte(string $importe): void
    {
        $this->importe = $importe;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeImmutable $fecha): void
    {
        $this->fecha = $fecha;
    }

    public function getCategoria(): ?string
    {
        return $this->categoria;
    }

    public function setCategoria(?string $categoria): void
    {
        $this->categoria = $categoria;
    }

    public function getNotas(): ?string
    {
        return $this->notas;
    }

    public function setNotas(?string $notas): void
    {
        $this->notas = $notas;
    }

    public function getFacturaPdfPath(): ?string
    {
        return $this->facturaPdfPath;
    }

    public function setFacturaPdfPath(?string $facturaPdfPath): void
    {
        $this->facturaPdfPath = $facturaPdfPath;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
