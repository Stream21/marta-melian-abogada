<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_requerimiento_documento')]
class ExpedienteRequerimientoDocumentoOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(name: 'requerimiento_id', type: Types::STRING, length: 36)]
    private string $requerimientoId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $nombre;

    #[ORM\Column(type: Types::TEXT)]
    private string $descripcion = '';

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $responsable;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $obligatorio = true;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $estado;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $archivoPath = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notaRechazo = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $orden = 0;

    #[ORM\Column(name: 'max_archivos', type: Types::INTEGER)]
    private int $maxArchivos = 1;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getRequerimientoId(): string
    {
        return $this->requerimientoId;
    }

    public function setRequerimientoId(string $requerimientoId): void
    {
        $this->requerimientoId = $requerimientoId;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function getResponsable(): string
    {
        return $this->responsable;
    }

    public function setResponsable(string $responsable): void
    {
        $this->responsable = $responsable;
    }

    public function isObligatorio(): bool
    {
        return $this->obligatorio;
    }

    public function setObligatorio(bool $obligatorio): void
    {
        $this->obligatorio = $obligatorio;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }

    public function getArchivoPath(): ?string
    {
        return $this->archivoPath;
    }

    public function setArchivoPath(?string $archivoPath): void
    {
        $this->archivoPath = $archivoPath;
    }

    public function getNotaRechazo(): ?string
    {
        return $this->notaRechazo;
    }

    public function setNotaRechazo(?string $notaRechazo): void
    {
        $this->notaRechazo = $notaRechazo;
    }

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): void
    {
        $this->orden = $orden;
    }

    public function getMaxArchivos(): int
    {
        return $this->maxArchivos;
    }

    public function setMaxArchivos(int $maxArchivos): void
    {
        $this->maxArchivos = $maxArchivos;
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
