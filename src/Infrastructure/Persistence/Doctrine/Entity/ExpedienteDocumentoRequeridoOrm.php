<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_documento_requerido')]
class ExpedienteDocumentoRequeridoOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $expedienteId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $nombre;

    #[ORM\Column(type: Types::TEXT)]
    private string $descripcion = '';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $obligatorio = true;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $tipo = 'individual';

    #[ORM\Column(type: Types::INTEGER)]
    private int $maxImagenes = 1;

    #[ORM\Column(type: Types::INTEGER)]
    private int $orden = 0;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $origen = 'tramite';

    #[ORM\Column(type: Types::STRING, length: 36, nullable: true)]
    private ?string $tramiteDocumentoRequeridoId = null;

    #[ORM\Column(type: Types::STRING, length: 36, nullable: true)]
    private ?string $servicioDocumentoRequeridoId = null;

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

    public function getExpedienteId(): string
    {
        return $this->expedienteId;
    }

    public function setExpedienteId(string $expedienteId): void
    {
        $this->expedienteId = $expedienteId;
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

    public function isObligatorio(): bool
    {
        return $this->obligatorio;
    }

    public function setObligatorio(bool $obligatorio): void
    {
        $this->obligatorio = $obligatorio;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): void
    {
        $this->tipo = $tipo;
    }

    public function getMaxImagenes(): int
    {
        return $this->maxImagenes;
    }

    public function setMaxImagenes(int $maxImagenes): void
    {
        $this->maxImagenes = $maxImagenes;
    }

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): void
    {
        $this->orden = $orden;
    }

    public function getOrigen(): string
    {
        return $this->origen;
    }

    public function setOrigen(string $origen): void
    {
        $this->origen = $origen;
    }

    public function getTramiteDocumentoRequeridoId(): ?string
    {
        return $this->tramiteDocumentoRequeridoId;
    }

    public function setTramiteDocumentoRequeridoId(?string $tramiteDocumentoRequeridoId): void
    {
        $this->tramiteDocumentoRequeridoId = $tramiteDocumentoRequeridoId;
    }

    public function getServicioDocumentoRequeridoId(): ?string
    {
        return $this->servicioDocumentoRequeridoId;
    }

    public function setServicioDocumentoRequeridoId(?string $servicioDocumentoRequeridoId): void
    {
        $this->servicioDocumentoRequeridoId = $servicioDocumentoRequeridoId;
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
