<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\AreaJuridicaId;
use App\Domain\ValueObject\ServicioId;

final readonly class Servicio
{
    public function __construct(
        private ServicioId $id,
        private string $nombre,
        private AreaJuridicaId $areaJuridicaId,
        private bool $activo = true,
        private ?string $areaCodigo = null,
        private ?string $areaNombre = null,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function id(): ServicioId
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function areaJuridicaId(): AreaJuridicaId
    {
        return $this->areaJuridicaId;
    }

    public function areaCodigo(): ?string
    {
        return $this->areaCodigo;
    }

    public function areaNombre(): ?string
    {
        return $this->areaNombre;
    }

    public function tipo(): TipoServicio
    {
        if (null === $this->areaCodigo) {
            return TipoServicio::fromAreaId($this->areaJuridicaId);
        }

        return TipoServicio::fromString($this->areaCodigo);
    }

    public function activo(): bool
    {
        return $this->activo;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function withDatos(string $nombre, AreaJuridicaId $areaJuridicaId, ?string $areaCodigo = null, ?string $areaNombre = null): self
    {
        return new self(
            $this->id,
            $nombre,
            $areaJuridicaId,
            $this->activo,
            $areaCodigo ?? $this->areaCodigo,
            $areaNombre ?? $this->areaNombre,
            $this->createdAt,
            $this->updatedAt,
        );
    }

    public function withActivo(bool $activo): self
    {
        return new self(
            $this->id,
            $this->nombre,
            $this->areaJuridicaId,
            $activo,
            $this->areaCodigo,
            $this->areaNombre,
            $this->createdAt,
            $this->updatedAt,
        );
    }
}
