<?php

declare(strict_types=1);

namespace App\Application\DTO\Holded;

/**
 * Datos de factura única por expediente.
 * totalWithTax = honorarios acordados (régimen exento: igual a la base).
 */
final readonly class ExpedienteInvoiceData
{
    /**
     * @param list<string> $taxes Claves de impuesto Holded (p. ej. s_iva_exento en v2).
     */
    public function __construct(
        public string $description,
        public float $totalWithTax,
        public string $itemName,
        public float $subtotal,
        public string $taxKey,
        public int $dateUnix,
        public array $taxes = [],
    ) {
    }
}
