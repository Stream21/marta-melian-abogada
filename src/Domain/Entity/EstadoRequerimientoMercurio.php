<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum EstadoRequerimientoMercurio: string
{
    case PendienteCliente = 'pendiente_cliente';
    case PendienteDespacho = 'pendiente_despacho';
    case Presentado = 'presentado';
    case Cerrado = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::PendienteCliente => 'Pendiente de cliente',
            self::PendienteDespacho => 'Pendiente de despacho',
            self::Presentado => 'Presentado',
            self::Cerrado => 'Cerrado',
        };
    }

    public function estaAbierto(): bool
    {
        return match ($this) {
            self::PendienteCliente, self::PendienteDespacho => true,
            self::Presentado, self::Cerrado => false,
        };
    }

    public static function fromString(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new \InvalidArgumentException('Estado de requerimiento Mercurio no válido.');
    }
}
