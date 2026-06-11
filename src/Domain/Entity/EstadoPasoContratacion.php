<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum EstadoPasoContratacion: string
{
    case Pendiente = 'pendiente';
    case RealizadoCliente = 'realizado_cliente';
    case ValidadoAbogado = 'validado_abogado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::RealizadoCliente => 'Realizado por cliente',
            self::ValidadoAbogado => 'Validado por abogado',
        };
    }
}
