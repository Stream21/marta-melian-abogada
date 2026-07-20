<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum OrigenDocumentoRequeridoExpediente: string
{
    case Servicio = 'servicio';
    case Tramite = 'tramite';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Servicio => 'Del servicio',
            self::Tramite => 'Del trámite',
            self::Manual => 'Añadido por el abogado',
        };
    }
}
