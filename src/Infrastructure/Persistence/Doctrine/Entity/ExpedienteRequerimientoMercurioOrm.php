<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_requerimiento_mercurio')]
class ExpedienteRequerimientoMercurioOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(name: 'expediente_id', type: Types::STRING, length: 36)]
    private string $expedienteId;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $tipo;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $destino;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $nombre;

    #[ORM\Column(type: Types::TEXT)]
    private string $descripcion = '';

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $estado;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $archivoPath = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $archivoNombre = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $justificantePresentacionPath = null;

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

    public function getExpedienteId(): string
    {
        return $this->expedienteId;
    }

    public function setExpedienteId(string $expedienteId): void
    {
        $this->expedienteId = $expedienteId;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): void
    {
        $this->tipo = $tipo;
    }

    public function getDestino(): string
    {
        return $this->destino;
    }

    public function setDestino(string $destino): void
    {
        $this->destino = $destino;
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

    public function getArchivoNombre(): ?string
    {
        return $this->archivoNombre;
    }

    public function setArchivoNombre(?string $archivoNombre): void
    {
        $this->archivoNombre = $archivoNombre;
    }

    public function getJustificantePresentacionPath(): ?string
    {
        return $this->justificantePresentacionPath;
    }

    public function setJustificantePresentacionPath(?string $justificantePresentacionPath): void
    {
        $this->justificantePresentacionPath = $justificantePresentacionPath;
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
