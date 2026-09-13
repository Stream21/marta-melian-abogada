<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Ramsey\Uuid\Uuid;

final readonly class ExpedienteRequerimientoCampoId
{
    public function __construct(
        private string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('ExpedienteRequerimientoCampoId no puede estar vacío.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }
}
