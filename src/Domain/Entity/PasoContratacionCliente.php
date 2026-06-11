<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum PasoContratacionCliente: string
{
    case Documentacion = 'documentacion';
    case DatosCliente = 'datos_cliente';
    case Firmas = 'firmas';
    case Pago = 'pago';

    public function label(): string
    {
        return match ($this) {
            self::Documentacion => 'Documentación',
            self::DatosCliente => 'Datos del cliente',
            self::Firmas => 'Firmas legales',
            self::Pago => 'Pago inicial',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Documentacion => 'Subida de documentación identificativa y requerida',
            self::DatosCliente => 'Extracción automática y datos complementarios del cliente',
            self::Firmas => 'Firma de hoja de encargo, designación y RGPD',
            self::Pago => 'Realización del pago según método acordado',
        };
    }

    public function orden(): int
    {
        return match ($this) {
            self::Documentacion => 1,
            self::DatosCliente => 2,
            self::Firmas => 3,
            self::Pago => 4,
        };
    }

    /**
     * @return PasoContratacionCliente[]
     */
    public static function ordenados(): array
    {
        $pasos = self::cases();
        usort($pasos, fn (self $a, self $b) => $a->orden() <=> $b->orden());

        return $pasos;
    }
}
