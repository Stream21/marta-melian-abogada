<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum MetodoPagoExpediente: string
{
    case Manual = 'manual';
    case Digital = 'digital';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Pago manual',
            self::Digital => 'Pago digital',
        };
    }
}
