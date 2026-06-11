<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Ramsey\Uuid\Uuid;

final readonly class ClienteId
{
    public function __construct(
        private string $value,
    ) {
        if ('' === trim($value)) {
            throw new \InvalidArgumentException('ClienteId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public function value(): string
    {
        return $this->value;
    }
}
