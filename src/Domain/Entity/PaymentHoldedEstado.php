<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum PaymentHoldedEstado: string
{
    case NoAplica = 'no_aplica';
    case PendienteSync = 'pendiente_sync';
    case Sincronizado = 'sincronizado';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::NoAplica => 'No aplica',
            self::PendienteSync => 'Pendiente de sincronización',
            self::Sincronizado => 'Sincronizado con Holded',
            self::Error => 'Error de sincronización',
        };
    }
}
