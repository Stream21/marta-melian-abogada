<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum ResponsableRequerimientoDocumento: string
{
    case Cliente = 'cliente';
    case Abogado = 'abogado';

    public function label(): string
    {
        return match ($this) {
            self::Cliente => 'Cliente',
            self::Abogado => 'Abogado / despacho',
        };
    }

    public static function fromString(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new \InvalidArgumentException('Responsable de documento no válido.');
    }
}
