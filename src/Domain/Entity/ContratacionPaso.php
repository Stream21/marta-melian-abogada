<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;

final readonly class ContratacionPaso
{
    public function __construct(
        private string $id,
        private ExpedienteId $expedienteId,
        private PasoContratacionCliente $paso,
        private EstadoPasoContratacion $estado,
        private ?\DateTimeImmutable $realizadoAt = null,
        private ?\DateTimeImmutable $validadoAt = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function expedienteId(): ExpedienteId
    {
        return $this->expedienteId;
    }

    public function paso(): PasoContratacionCliente
    {
        return $this->paso;
    }

    public function estado(): EstadoPasoContratacion
    {
        return $this->estado;
    }

    public function realizadoAt(): ?\DateTimeImmutable
    {
        return $this->realizadoAt;
    }

    public function validadoAt(): ?\DateTimeImmutable
    {
        return $this->validadoAt;
    }

    public function marcarRealizadoCliente(): self
    {
        return new self(
            $this->id,
            $this->expedienteId,
            $this->paso,
            EstadoPasoContratacion::RealizadoCliente,
            new \DateTimeImmutable('now'),
            $this->validadoAt,
        );
    }

    public function marcarValidadoAbogado(): self
    {
        return new self(
            $this->id,
            $this->expedienteId,
            $this->paso,
            EstadoPasoContratacion::ValidadoAbogado,
            $this->realizadoAt,
            new \DateTimeImmutable('now'),
        );
    }
}
