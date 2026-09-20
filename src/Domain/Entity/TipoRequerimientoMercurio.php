<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum TipoRequerimientoMercurio: string
{
    case Documentacion = 'documentacion';
    case Tasas = 'tasas';

    public function label(): string
    {
        return match ($this) {
            self::Documentacion => 'Documentación adjunta',
            self::Tasas => 'Tasas',
        };
    }

    public static function fromString(string $value): self
    {
        $normalized = match ($value) {
            'documento', 'escrito' => self::Documentacion->value,
            default => $value,
        };

        return self::tryFrom($normalized)
            ?? throw new \InvalidArgumentException('Tipo de requerimiento Mercurio no válido.');
    }
}
