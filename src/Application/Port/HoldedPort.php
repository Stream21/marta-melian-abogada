<?php

declare(strict_types=1);

namespace App\Application\Port;

interface HoldedPort
{
    /**
     * Crea una factura en Holded con los datos indicados.
     * Retorna el ID de la factura en Holded.
     */
    public function createInvoice(array $invoiceData): string;

    /**
     * Marca la factura como cobrada en Holded.
     */
    public function markAsPaid(string $holdedInvoiceId): void;

    /**
     * Descarga el PDF de la factura desde Holded.
     * Retorna el contenido binario del PDF.
     */
    public function getInvoicePdf(string $holdedInvoiceId): string;
}
