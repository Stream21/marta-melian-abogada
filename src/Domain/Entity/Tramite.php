<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ServicioId;
use App\Domain\ValueObject\TramiteId;

final readonly class Tramite
{
    public function __construct(
        private TramiteId $id,
        private ServicioId $servicioId,
        private string $nombre,
        private float $honorarios,
        private PlataformaTramitacion $plataforma,
        private bool $requiereProcurador,
        private bool $activo = true,
        private bool $requiereOtpFirma = true,
        private ?string $servicioNombre = null,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function id(): TramiteId
    {
        return $this->id;
    }

    public function servicioId(): ServicioId
    {
        return $this->servicioId;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function honorarios(): float
    {
        return $this->honorarios;
    }

    public function plataforma(): PlataformaTramitacion
    {
        return $this->plataforma;
    }

    public function requiereProcurador(): bool
    {
        return $this->requiereProcurador;
    }

    public function activo(): bool
    {
        return $this->activo;
    }

    public function requiereOtpFirma(): bool
    {
        return $this->requiereOtpFirma;
    }

    public function servicioNombre(): ?string
    {
        return $this->servicioNombre;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function withDatos(
        ServicioId $servicioId,
        string $nombre,
        float $honorarios,
        PlataformaTramitacion $plataforma,
        bool $requiereProcurador,
    ): self {
        return new self(
            $this->id,
            $servicioId,
            $nombre,
            $honorarios,
            $plataforma,
            $requiereProcurador,
            $this->activo,
            $this->requiereOtpFirma,
            $this->servicioNombre,
            $this->createdAt,
            $this->updatedAt,
        );
    }

    public function withRequiereOtpFirma(bool $requiereOtpFirma): self
    {
        return new self(
            $this->id,
            $this->servicioId,
            $this->nombre,
            $this->honorarios,
            $this->plataforma,
            $this->requiereProcurador,
            $this->activo,
            $requiereOtpFirma,
            $this->servicioNombre,
            $this->createdAt,
            $this->updatedAt,
        );
    }

    public function withActivo(bool $activo): self
    {
        return new self(
            $this->id,
            $this->servicioId,
            $this->nombre,
            $this->honorarios,
            $this->plataforma,
            $this->requiereProcurador,
            $activo,
            $this->requiereOtpFirma,
            $this->servicioNombre,
            $this->createdAt,
            $this->updatedAt,
        );
    }
}
