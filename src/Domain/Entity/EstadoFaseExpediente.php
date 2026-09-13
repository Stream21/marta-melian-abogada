<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum EstadoFaseExpediente: string
{
    case PendienteCliente = 'pendiente_cliente';
    case PendienteFirma = 'pendiente_firma';
    case PendientePago = 'pendiente_pago';
    case Completada = 'completada';
    case DocumentacionEnProgreso = 'documentacion_en_progreso';
    case DocumentacionListo = 'documentacion_listo';

    public function label(): string
    {
        return match ($this) {
            self::PendienteCliente => 'Pendiente de cliente',
            self::PendienteFirma => 'Pendiente de firma',
            self::PendientePago => 'Pendiente de pago',
            self::Completada => 'Completada',
            self::DocumentacionEnProgreso => 'En progreso',
            self::DocumentacionListo => 'Listo para presentación',
        };
    }
}
