<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Las 3 subfases de la fase de negocio «contratación».
 * Expuestas en API como subfaseContratacion para listados y portal.
 */
enum PasoContratacionCliente: string
{
    case DatosCliente = 'datos_cliente';
    case Firmas = 'firmas';
    case Pago = 'pago';

    public const TOTAL = 3;

    public function label(): string
    {
        return match ($this) {
            self::DatosCliente => 'Identidad y datos',
            self::Firmas => 'Firmas legales',
            self::Pago => 'Pago inicial',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::DatosCliente => 'Escaneo del DNI/NIE/pasaporte y verificación de datos personales',
            self::Firmas => 'Firma de hoja de encargo, designación y RGPD (contrato protección de datos)',
            self::Pago => 'Instrucciones de pago según método acordado (el abogado confirma el cobro manual)',
        };
    }

    public function orden(): int
    {
        return match ($this) {
            self::DatosCliente => 1,
            self::Firmas => 2,
            self::Pago => 3,
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
