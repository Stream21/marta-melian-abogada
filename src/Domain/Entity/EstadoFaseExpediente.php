<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum EstadoFaseExpediente: string
{
    case PendienteCliente = 'pendiente_cliente';
    case PendienteFirma = 'pendiente_firma';
    case PendientePago = 'pendiente_pago';
    case Completada = 'completada';
    case RequerimientosEnProgreso = 'requerimientos_en_progreso';
    case RequerimientosListo = 'requerimientos_listo';

    public function label(): string
    {
        return match ($this) {
            self::PendienteCliente => 'Pendiente de cliente',
            self::PendienteFirma => 'Pendiente de firma',
            self::PendientePago => 'Pendiente de pago',
            self::Completada => 'Completada',
            self::RequerimientosEnProgreso => 'En progreso',
            self::RequerimientosListo => 'Listo para presentación',
        };
    }
}
