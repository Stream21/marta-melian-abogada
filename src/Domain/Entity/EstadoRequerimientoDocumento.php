<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum EstadoRequerimientoDocumento: string
{
    case Pendiente = 'pendiente';
    case Entregado = 'entregado';
    case Validado = 'validado';
    case Rechazado = 'rechazado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Entregado => 'Entregado (pendiente revisión)',
            self::Validado => 'Validado',
            self::Rechazado => 'Devuelto',
        };
    }

    public static function fromString(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new \InvalidArgumentException('Estado de documento de requerimiento no válido.');
    }
}
