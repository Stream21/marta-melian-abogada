<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\DTO\Holded\ClienteHoldedData;
use App\Application\DTO\Holded\ExpedienteInvoiceData;

/**
 * Puerto unificado Holded (mock/v1 con header `key` o API v2 con Bearer PAT).
 * v2: /contacts, /invoices, /invoices/{id}/payments, /invoices/{id}/pdf.
 * v1/mock: /contacts, /documents/invoice, .../pay, .../pdf.
 */
interface HoldedPort
{
    /**
     * Resuelve el id de contacto Holded para facturar:
     * 1) reutiliza holdedContactId local si ya existe;
     * 2) si no, busca en Holded por documento (NIF/CIF → `code`);
     * 3) si no existe, crea el contacto.
     * En todos los casos devuelve el id (el caller lo persiste en Cliente.holdedContactId).
     */
    public function findOrCreateContact(ClienteHoldedData $clientData): string;

    /**
     * Crea factura por el total del servicio. Devuelve el id de Holded.
     */
    public function createInvoice(string $holdedContactId, ExpedienteInvoiceData $caseData): string;

    /**
     * Imputa un cobro parcial/total contra la factura (POST .../pay).
     */
    public function recordPayment(
        string $holdedInvoiceId,
        float $amount,
        string $description,
        string $paymentMethod,
    ): void;

    /**
     * Descarga el PDF binario de la factura.
     */
    public function getInvoicePdf(string $holdedInvoiceId): string;

    /**
     * Lista contactos (módulo facturación legacy / UI).
     *
     * @return list<array<string, mixed>>
     */
    public function listContacts(): array;
}
