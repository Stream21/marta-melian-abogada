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
        private ?string $notaDevolucion = null,
        /** @var list<string> */
        private array $motivosDevolucion = [],
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

    public function notaDevolucion(): ?string
    {
        return $this->notaDevolucion;
    }

    /**
     * @return list<string>
     */
    public function motivosDevolucion(): array
    {
        return $this->motivosDevolucion;
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
            null,
            [],
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
            null,
            [],
        );
    }

    /**
     * @param list<string> $motivos
     */
    public function devolverConNota(string $nota, array $motivos = []): self
    {
        $nota = trim($nota);
        if ('' === $nota) {
            throw new \InvalidArgumentException('La nota de devolución es obligatoria.');
        }

        return new self(
            $this->id,
            $this->expedienteId,
            $this->paso,
            EstadoPasoContratacion::Pendiente,
            null,
            null,
            $nota,
            array_values(array_unique($motivos)),
        );
    }

    public function notificarConNota(string $nota): self
    {
        $nota = trim($nota);
        if ('' === $nota) {
            throw new \InvalidArgumentException('La nota es obligatoria.');
        }

        return new self(
            $this->id,
            $this->expedienteId,
            $this->paso,
            $this->estado,
            $this->realizadoAt,
            $this->validadoAt,
            $nota,
            $this->motivosDevolucion,
        );
    }
}
