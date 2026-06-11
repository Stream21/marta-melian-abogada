<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum ClienteHoldedEstado: string
{
    case Oportunidad = 'oportunidad';
    case Sincronizado = 'sincronizado';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Oportunidad => 'Oportunidad',
            self::Sincronizado => 'Sincronizado con Holded',
            self::Error => 'Error de sincronización',
        };
    }
}
