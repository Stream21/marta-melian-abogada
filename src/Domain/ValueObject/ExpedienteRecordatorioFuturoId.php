<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class ExpedienteRecordatorioFuturoId
{
    public function __construct(private string $value)
    {
        if ('' === trim($value)) {
            throw new \InvalidArgumentException('El id de recordatorio es obligatorio.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
