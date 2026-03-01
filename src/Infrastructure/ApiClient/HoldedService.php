<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiClient;

use App\Application\Port\HoldedPort;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Implementación del puerto Holded: facturación vía API REST de Holded.
 * Ver documentación Holded para endpoints exactos (crear factura, marcar cobrada, descargar PDF).
 */
final class HoldedService implements HoldedPort
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $holdedApiKey,
        private string $holdedBaseUrl,
    ) {
    }

    /**
     * Crea una factura en Holded.
     * Llamada POST al endpoint de facturas con client, reference, amount.
     */
    public function createInvoice(array $invoiceData): string
    {
        $response = $this->httpClient->request('POST', $this->holdedBaseUrl . '/invoicing/v1/invoices', [
            'headers' => [
                'key' => $this->holdedApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'contactName' => $invoiceData['client'] ?? '',
                'desc' => $invoiceData['reference'] ?? '',
                'lines' => [
                    [
                        'name' => 'Servicios',
                        'quantity' => 1,
                        'price' => (float) ($invoiceData['amount'] ?? 0),
                    ],
                ],
            ],
        ]);

        $data = $response->toArray();

        return (string) ($data['id'] ?? $data['invoiceId'] ?? '');
    }

    /**
     * Marca la factura como cobrada en Holded.
     * Llamada PATCH o PUT al endpoint de la factura para actualizar estado a paid.
     */
    public function markAsPaid(string $holdedInvoiceId): void
    {
        $this->httpClient->request('PATCH', $this->holdedBaseUrl . '/invoicing/v1/invoices/' . $holdedInvoiceId, [
            'headers' => [
                'key' => $this->holdedApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => ['paid' => true],
        ]);
    }

    /**
     * Descarga el PDF de la factura desde Holded.
     * Llamada GET al endpoint de exportación PDF de la factura.
     */
    public function getInvoicePdf(string $holdedInvoiceId): string
    {
        $response = $this->httpClient->request('GET', $this->holdedBaseUrl . '/invoicing/v1/invoices/' . $holdedInvoiceId . '/pdf', [
            'headers' => [
                'key' => $this->holdedApiKey,
            ],
        ]);

        return $response->getContent();
    }
}
