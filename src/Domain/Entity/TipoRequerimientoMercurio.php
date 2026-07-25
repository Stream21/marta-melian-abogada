<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum TipoRequerimientoMercurio: string
{
    case Documento = 'documento';
    case Escrito = 'escrito';

    public function label(): string
    {
        return match ($this) {
            self::Documento => 'Documento',
            self::Escrito => 'Escrito',
        };
    }

    public static function fromString(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new \InvalidArgumentException('Tipo de requerimiento Mercurio no válido.');
    }
}
