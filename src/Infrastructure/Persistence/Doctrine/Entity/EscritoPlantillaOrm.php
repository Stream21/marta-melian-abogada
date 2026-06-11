<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'escrito_plantilla')]
#[ORM\UniqueConstraint(name: 'UNIQ_ESCRITO_PLANTILLA_TRAMITE_TIPO', columns: ['tramite_id', 'tipo'])]
class EscritoPlantillaOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $tramiteId;

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $tipo;

    /** @var list<array<string, mixed>> */
    #[ORM\Column(type: Types::JSON)]
    private array $bloques = [];

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

    public function getTramiteId(): string
    {
        return $this->tramiteId;
    }

    public function setTramiteId(string $tramiteId): void
    {
        $this->tramiteId = $tramiteId;
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
     * @return list<array<string, mixed>>
     */
    public function getBloques(): array
    {
        return $this->bloques;
    }

    /**
     * @param list<array<string, mixed>> $bloques
     */
    public function setBloques(array $bloques): void
    {
        $this->bloques = $bloques;
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
