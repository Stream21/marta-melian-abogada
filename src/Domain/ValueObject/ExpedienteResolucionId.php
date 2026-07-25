<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class ExpedienteResolucionId
{
    public function __construct(private string $value)
    {
        if ('' === trim($value)) {
            throw new \InvalidArgumentException('El id de resolución es obligatorio.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
