<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\EscritoPlantillaId;
use App\Domain\ValueObject\TramiteId;

final readonly class EscritoPlantilla
{
    /**
     * @param list<array<string, mixed>> $bloques
     */
    public function __construct(
        private EscritoPlantillaId $id,
        private TramiteId $tramiteId,
        private TipoEscrito $tipo,
        private array $bloques,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function id(): EscritoPlantillaId
    {
        return $this->id;
    }

    public function tramiteId(): TramiteId
    {
        return $this->tramiteId;
    }

    public function tipo(): TipoEscrito
    {
        return $this->tipo;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function bloques(): array
    {
        return $this->bloques;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param list<array<string, mixed>> $bloques
     */
    public function withBloques(array $bloques): self
    {
        return new self(
            $this->id,
            $this->tramiteId,
            $this->tipo,
            $bloques,
            $this->createdAt,
            $this->updatedAt,
        );
    }
}
