<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class ExpedienteRequerimientoMercurioId
{
    public function __construct(private string $value)
    {
        if ('' === trim($value)) {
            throw new \InvalidArgumentException('El id de requerimiento Mercurio es obligatorio.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
