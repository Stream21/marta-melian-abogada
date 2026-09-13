<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_requerimiento_campo')]
class ExpedienteRequerimientoCampoOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(name: 'requerimiento_id', type: Types::STRING, length: 36)]
    private string $requerimientoId;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $clave;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $etiqueta;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $tipo;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $opcionesJson = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $obligatorio = true;

    #[ORM\Column(type: Types::INTEGER)]
    private int $orden = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $valor = null;

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

    public function getClave(): string
    {
        return $this->clave;
    }

    public function setClave(string $clave): void
    {
        $this->clave = $clave;
    }

    public function getEtiqueta(): string
    {
        return $this->etiqueta;
    }

    public function setEtiqueta(string $etiqueta): void
    {
        $this->etiqueta = $etiqueta;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): void
    {
        $this->tipo = $tipo;
    }

    /**
     * @return list<string>|null
     */
    public function getOpcionesJson(): ?array
    {
        return $this->opcionesJson;
    }

    /**
     * @param list<string>|null $opcionesJson
     */
    public function setOpcionesJson(?array $opcionesJson): void
    {
        $this->opcionesJson = $opcionesJson;
    }

    public function isObligatorio(): bool
    {
        return $this->obligatorio;
    }

    public function setObligatorio(bool $obligatorio): void
    {
        $this->obligatorio = $obligatorio;
    }

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): void
    {
        $this->orden = $orden;
    }

    public function getValor(): ?string
    {
        return $this->valor;
    }

    public function setValor(?string $valor): void
    {
        $this->valor = $valor;
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
