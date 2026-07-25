<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum SubfaseTramitacion: string
{
    case PreparacionPresentacion = 'preparacion_presentacion';
    case PendienteRecepcion = 'pendiente_recepcion';
    case EnSeguimiento = 'en_seguimiento';
    case RequerimientoAbierto = 'requerimiento_abierto';
    case ListoResolucion = 'listo_resolucion';

    public function label(): string
    {
        return match ($this) {
            self::PreparacionPresentacion => 'Preparación de presentación',
            self::PendienteRecepcion => 'Pendiente de recepción',
            self::EnSeguimiento => 'En seguimiento',
            self::RequerimientoAbierto => 'Requerimiento abierto',
            self::ListoResolucion => 'Listo para resolución',
        };
    }

    /** Quién tiene la pelota a efectos de UI (despacho vs Mercurio). */
    public function actorBandeja(): string
    {
        return match ($this) {
            self::PreparacionPresentacion,
            self::RequerimientoAbierto,
            self::ListoResolucion => 'despacho',
            self::PendienteRecepcion,
            self::EnSeguimiento => 'mercurio',
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

        return self::tryFrom($value);
    }
}
