<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum FaseNegocioExpediente: string
{
    case Contratacion = 'contratacion';
    case Documentacion = 'documentacion';
    case Tramitacion = 'tramitacion';
    case Resolucion = 'resolucion';

    public function label(): string
    {
        return match ($this) {
            self::Contratacion => 'Contratación',
            self::Documentacion => 'Documentación',
            self::Tramitacion => 'Tramitación',
            self::Resolucion => 'Resolución',
        };
    }
}
