<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum SubfaseTramitacion: string
{
    case PendienteTramitacion = 'pendiente_tramitacion';
    case Tramitado = 'tramitado';
    case PendienteRequerimiento = 'pendiente_requerimiento';

    public function label(): string
    {
        return match ($this) {
            self::PendienteTramitacion => 'Pendiente de tramitación',
            self::Tramitado => 'Tramitado',
            self::PendienteRequerimiento => 'Pendiente de requerimiento',
        };
    }

    /** Quién tiene la pelota a efectos de UI (despacho vs Mercurio). */
    public function actorBandeja(): string
    {
        return match ($this) {
            self::PendienteTramitacion,
            self::PendienteRequerimiento => 'despacho',
            self::Tramitado => 'mercurio',
        };
    }

    public function actorBandejaLabel(): string
    {
        return 'despacho' === $this->actorBandeja() ? 'En despacho' : 'En Mercurio';
    }

    public static function fromString(?string $value): ?self
    {
        if (null === $value || '' === $value) {
            return null;
        }

        // Compatibilidad con valores previos a la consolidación de subfases.
        return match ($value) {
            'preparacion_presentacion' => self::PendienteTramitacion,
            'pendiente_recepcion', 'en_seguimiento', 'listo_resolucion' => self::Tramitado,
            'requerimiento_abierto', 'recopilacion_datos' => self::PendienteRequerimiento,
            default => self::tryFrom($value),
        };
    }
}
