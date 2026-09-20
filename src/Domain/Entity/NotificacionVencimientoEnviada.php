<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;

final readonly class NotificacionVencimientoEnviada
{
    public function __construct(
        private string $id,
        private ExpedienteId $expedienteId,
        private \DateTimeImmutable $fechaVencimiento,
        private int $diaRelativo,
        private \DateTimeImmutable $enviadoAt,
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

    public function fechaVencimiento(): \DateTimeImmutable
    {
        return $this->fechaVencimiento;
    }

    public function diaRelativo(): int
    {
        return $this->diaRelativo;
    }

    public function enviadoAt(): \DateTimeImmutable
    {
        return $this->enviadoAt;
    }
}
