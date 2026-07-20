<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'servicio_documento_requerido')]
class ServicioDocumentoRequeridoOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: ServicioOrm::class)]
    #[ORM\JoinColumn(name: 'servicio_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ServicioOrm $servicio;

    #[ORM\Column(type: Types::INTEGER)]
    private int $fase = 2;

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

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $formatos = ['pdf'];

    #[ORM\Column(type: Types::INTEGER)]
    private int $orden = 0;

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

    public function getFase(): int
    {
        return $this->fase;
    }

    public function setFase(int $fase): void
    {
        $this->fase = $fase;
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

    /**
     * @return list<string>
     */
    public function getFormatos(): array
    {
        return $this->formatos;
    }

    /**
     * @param list<string> $formatos
     */
    public function setFormatos(array $formatos): void
    {
        $this->formatos = $formatos;
    }

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): void
    {
        $this->orden = $orden;
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
