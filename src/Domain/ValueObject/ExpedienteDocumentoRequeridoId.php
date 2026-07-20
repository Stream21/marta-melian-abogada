<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class ExpedienteDocumentoRequeridoId
{
    public function __construct(
        private string $value,
    ) {
        if ('' === trim($value)) {
            throw new \InvalidArgumentException('El identificador del documento requerido no puede estar vacío.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
