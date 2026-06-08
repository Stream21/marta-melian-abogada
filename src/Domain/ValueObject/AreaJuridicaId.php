<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class AreaJuridicaId
{
    public function __construct(
        private string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('AreaJuridicaId no puede estar vacío.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
