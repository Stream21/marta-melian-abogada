<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum FaseNegocioExpediente: string
{
    case Contratacion = 'contratacion';
    case Requerimientos = 'requerimientos';
    case Tramitacion = 'tramitacion';
    case Resolucion = 'resolucion';

    public function label(): string
    {
        return match ($this) {
            self::Contratacion => 'Contratación',
            self::Requerimientos => 'Requerimientos',
            self::Tramitacion => 'Tramitación',
            self::Resolucion => 'Resolución',
        };
    }
}
