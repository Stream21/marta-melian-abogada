<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum DestinoRequerimientoMercurio: string
{
    case Cliente = 'cliente';
    case Despacho = 'despacho';

    public function label(): string
    {
        return match ($this) {
            self::Cliente => 'Para el cliente',
            self::Despacho => 'Interno del despacho',
        };
    }

    public static function fromString(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new \InvalidArgumentException('Destino de requerimiento Mercurio no válido.');
    }
}
