<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum TipoEscaneoDocumentoIdentidad: string
{
    case DniNie = 'dni_nie';
    case Pasaporte = 'pasaporte';

    public function label(): string
    {
        return match ($this) {
            self::DniNie => 'DNI / NIE',
            self::Pasaporte => 'Pasaporte',
        };
    }

    public function requiereReverso(): bool
    {
        return self::DniNie === $this;
    }
}
