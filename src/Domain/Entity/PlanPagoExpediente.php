<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum PlanPagoExpediente: string
{
    case Unico = 'unico';
    case Fraccionado = 'fraccionado';

    public function label(): string
    {
        return match ($this) {
            self::Unico => 'Pago único',
            self::Fraccionado => 'Pago fraccionado',
        };
    }
}
