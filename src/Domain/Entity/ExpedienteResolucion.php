<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteResolucionId;

final readonly class ExpedienteResolucion
{
    /**
     * @param list<GestionPostResolucion> $gestiones
     */
    public function __construct(
        private ExpedienteResolucionId $id,
        private ExpedienteId $expedienteId,
        private OutcomeResolucion $outcome,
        private string $resolucionPath,
        private \DateTimeImmutable $fechaNotificacion,
        private array $gestiones = [],
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('now'),
        private \DateTimeImmutable $updatedAt = new \DateTimeImmutable('now'),
    ) {
        if ('' === trim($resolucionPath)) {
            throw new \InvalidArgumentException('El archivo de resolución es obligatorio.');
        }
    }

    public function id(): ExpedienteResolucionId
    {
        return $this->id;
    }

    public function expedienteId(): ExpedienteId
    {
        return $this->expedienteId;
    }

    public function outcome(): OutcomeResolucion
    {
        return $this->outcome;
    }

    public function resolucionPath(): string
    {
        return $this->resolucionPath;
    }

    public function fechaNotificacion(): \DateTimeImmutable
    {
        return $this->fechaNotificacion;
    }

    /**
     * @return list<GestionPostResolucion>
     */
    public function gestiones(): array
    {
        return $this->gestiones;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param list<GestionPostResolucion> $gestiones
     */
    public function withGestiones(array $gestiones): self
    {
        return new self(
            $this->id,
            $this->expedienteId,
            $this->outcome,
            $this->resolucionPath,
            $this->fechaNotificacion,
            $gestiones,
            $this->createdAt,
            new \DateTimeImmutable('now'),
        );
    }

    public function gestionesCompletas(): bool
    {
        if ([] === $this->gestiones) {
            return true;
        }
        foreach ($this->gestiones as $g) {
            if (!$g->hecho()) {
                return false;
            }
        }

        return true;
    }
}
