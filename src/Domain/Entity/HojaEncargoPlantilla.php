<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\HojaEncargoPlantillaId;
use App\Domain\ValueObject\TramiteId;

final readonly class HojaEncargoPlantilla
{
    /**
     * @param list<array<string, mixed>> $bloques
     */
    public function __construct(
        private HojaEncargoPlantillaId $id,
        private TramiteId $tramiteId,
        private array $bloques,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function id(): HojaEncargoPlantillaId
    {
        return $this->id;
    }

    public function tramiteId(): TramiteId
    {
        return $this->tramiteId;
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
            $bloques,
            $this->createdAt,
            $this->updatedAt,
        );
    }
}
