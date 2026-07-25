<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum OutcomeResolucion: string
{
    case Concedida = 'concedida';
    case Denegada = 'denegada';

    public function label(): string
    {
        return match ($this) {
            self::Concedida => 'Concedida',
            self::Denegada => 'Denegada',
        };
    }
}
