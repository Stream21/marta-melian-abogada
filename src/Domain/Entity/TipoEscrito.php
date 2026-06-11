<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum TipoEscrito: string
{
    case HojaEncargo = 'hoja_encargo';
    case Designacion = 'designacion';
    case Rgpd = 'rgpd';

    public function label(): string
    {
        return match ($this) {
            self::HojaEncargo => 'Hoja de encargo',
            self::Designacion => 'Designación',
            self::Rgpd => 'RGPD',
        };
    }

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? throw new \InvalidArgumentException('Tipo de escrito no válido: ' . $value);
    }
}
