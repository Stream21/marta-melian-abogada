<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum TipoDocumentoRequerido: string
{
    case Individual = 'individual';
    case Conjunto = 'conjunto';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Documento individual',
            self::Conjunto => 'Conjunto de archivos',
        };
    }

    public static function fromString(string $value): self
    {
        return match ($value) {
            'individual' => self::Individual,
            'conjunto' => self::Conjunto,
            default => throw new \InvalidArgumentException('Tipo de documento no válido.'),
        };
    }
}
