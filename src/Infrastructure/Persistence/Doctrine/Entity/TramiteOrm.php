<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tramite')]
class TramiteOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: ServicioOrm::class)]
    #[ORM\JoinColumn(name: 'servicio_id', referencedColumnName: 'id', nullable: false)]
    private ServicioOrm $servicio;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $nombre;

    #[ORM\Column(type: Types::FLOAT)]
    private float $honorarios = 0.0;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $plataforma = 'mercurio';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $requiereProcurador = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $activo = true;

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

    public function getServicio(): ServicioOrm
    {
        return $this->servicio;
    }

    public function setServicio(ServicioOrm $servicio): void
    {
        $this->servicio = $servicio;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function getHonorarios(): float
    {
        return $this->honorarios;
    }

    public function setHonorarios(float $honorarios): void
    {
        $this->honorarios = $honorarios;
    }

    public function getPlataforma(): string
    {
        return $this->plataforma;
    }

    public function setPlataforma(string $plataforma): void
    {
        $this->plataforma = $plataforma;
    }

    public function isRequiereProcurador(): bool
    {
        return $this->requiereProcurador;
    }

    public function setRequiereProcurador(bool $requiereProcurador): void
    {
        $this->requiereProcurador = $requiereProcurador;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
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
