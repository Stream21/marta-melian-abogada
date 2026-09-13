<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiClient;

use App\Application\DTO\Holded\ClienteHoldedData;
use App\Application\DTO\Holded\ExpedienteInvoiceData;
use App\Application\Port\HoldedPort;
use App\Domain\Exception\HoldedApiException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Cliente HTTP contra Holded: API v2 (Bearer PAT) o Invoicing API v1 mock/legacy (header key).
 */
final class HoldedService implements HoldedPort
{
    private const TIMEOUT = 15.0;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $holdedApiKey,
        private string $holdedApiBaseUrl,
        private LoggerInterface $logger,
    ) {
    }

    public function findOrCreateContact(ClienteHoldedData $clientData): string
    {
        $existing = trim((string) ($clientData->existingHoldedContactId ?? ''));
        if ('' !== $existing) {
            return $existing;
        }

        $documentNumber = trim($clientData->documentNumber);
        if ('' !== $documentNumber) {
            $foundId = $this->findContactIdByDocument($documentNumber);
            if (null !== $foundId) {
                return $foundId;
            }
        }

        $data = $this->request('POST', '/contacts', $this->buildContactPayload($clientData));

        $id = (string) ($data['id'] ?? '');
        if ('' === $id) {
            throw new HoldedApiException('Holded no devolvió id de contacto.');
        }

        return $id;
    }

    /**
     * Busca contacto en Holded por NIF/CIF (`code`). v2 filtra por query; v1/mock puede devolver lista y se empareja en local.
     */
    private function findContactIdByDocument(string $documentNumber): ?string
    {
        $normalized = $this->normalizeDocumentCode($documentNumber);
        $data = $this->request(
            'GET',
            '/contacts?code=' . rawurlencode($documentNumber) . '&limit=100',
        );

        foreach ($this->extractContactsList($data) as $contact) {
            if (!is_array($contact)) {
                continue;
            }
            $code = $this->normalizeDocumentCode((string) ($contact['code'] ?? $contact['vat_number'] ?? ''));
            if ('' !== $code && $code === $normalized) {
                $id = trim((string) ($contact['id'] ?? ''));
                if ('' !== $id) {
                    return $id;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<mixed>
     */
    private function extractContactsList(array $data): array
    {
        if (isset($data['items']) && is_array($data['items'])) {
            return array_values($data['items']);
        }

        if (isset($data['contacts']) && is_array($data['contacts'])) {
            return array_values($data['contacts']);
        }

        if (array_is_list($data)) {
            return $data;
        }

        return [];
    }

    private function normalizeDocumentCode(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($value)) ?? '');
    }

    public function createInvoice(string $holdedContactId, ExpedienteInvoiceData $caseData): string
    {
        $path = $this->usesApiV2() ? '/invoices' : '/documents/invoice';
        $data = $this->request('POST', $path, $this->buildInvoicePayload($holdedContactId, $caseData));

        $id = (string) ($data['id'] ?? $data['invoiceId'] ?? '');
        if ('' === $id) {
            throw new HoldedApiException('Holded no devolvió id de factura.');
        }

        return $id;
    }

    public function recordPayment(
        string $holdedInvoiceId,
        float $amount,
        string $description,
        string $paymentMethod,
    ): void {
        if ($this->usesApiV2()) {
            $path = '/invoices/' . rawurlencode($holdedInvoiceId) . '/payments';
            $payload = [
                'date' => date('Y-m-d'),
                'amount' => number_format(round($amount, 2), 2, '.', ''),
                'description' => $this->appendPaymentMethodToDescription($description, $paymentMethod),
            ];
        } else {
            $path = '/documents/invoice/' . rawurlencode($holdedInvoiceId) . '/pay';
            $payload = [
                'date' => time(),
                'amount' => round($amount, 2),
                'desc' => $description,
                'paymentMethod' => $paymentMethod,
            ];
        }

        $this->request('POST', $path, $payload);
    }

    public function getInvoicePdf(string $holdedInvoiceId): string
    {
        $segment = $this->usesApiV2() ? 'invoices' : 'documents/invoice';
        $url = rtrim($this->holdedApiBaseUrl, '/') . '/' . $segment . '/'
            . rawurlencode($holdedInvoiceId) . '/pdf';

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $this->headers(),
                'timeout' => self::TIMEOUT,
            ]);
            $status = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Holded PDF transport error', ['error' => $e->getMessage()]);
            throw new HoldedApiException('Timeout o error de red al descargar PDF de Holded: ' . $e->getMessage(), 0, $e);
        }

        if ($status >= 400) {
            $this->logger->error('Holded PDF HTTP error', ['status' => $status, 'body' => $content]);
            throw HoldedApiException::fromResponse($status, $content);
        }

        if (str_starts_with($content, '%PDF-')) {
            return $content;
        }

        try {
            $json = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new HoldedApiException('Respuesta PDF de Holded no reconocida.', $status, $e);
        }

        $b64 = (string) ($json['data'] ?? $json['base64'] ?? '');
        if ('' === $b64) {
            throw new HoldedApiException('PDF Holded sin campo data/base64.');
        }

        $decoded = base64_decode($b64, true);
        if (false === $decoded || !str_starts_with($decoded, '%PDF-')) {
            throw new HoldedApiException('No se pudo decodificar el PDF de Holded.');
        }

        return $decoded;
    }

    public function listContacts(): array
    {
        return $this->extractContactsList($this->request('GET', '/contacts'));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContactPayload(ClienteHoldedData $clientData): array
    {
        if ($this->usesApiV2()) {
            $payload = [
                'name' => $clientData->name,
                'email' => $clientData->email,
                'code' => $clientData->documentNumber,
                'type' => ['client'],
                'is_person' => true,
                'bill_address' => [
                    'address' => $clientData->address,
                    'city' => $clientData->city,
                    'postal_code' => $clientData->postalCode,
                    'country_code' => $clientData->countryCode,
                ],
            ];
            if ('' !== trim($clientData->phone)) {
                $payload['phone'] = $clientData->phone;
            }

            return $payload;
        }

        $payload = [
            'name' => $clientData->name,
            'email' => $clientData->email,
            'code' => $clientData->documentNumber,
            'type' => 'client',
            'isperson' => true,
            'billAddress' => [
                'address' => $clientData->address,
                'city' => $clientData->city,
                'postalCode' => $clientData->postalCode,
                'country' => $clientData->countryCode,
            ],
        ];
        if ('' !== trim($clientData->phone)) {
            $payload['phone'] = $clientData->phone;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInvoicePayload(string $holdedContactId, ExpedienteInvoiceData $caseData): array
    {
        $lineItem = [
            'name' => $caseData->itemName,
            'units' => 1,
            'taxes' => $caseData->taxes,
        ];

        if ($this->usesApiV2()) {
            $lineItem['type'] = 'service';
            $lineItem['price'] = $caseData->subtotal;

            return [
                'contact_id' => $holdedContactId,
                'description' => $caseData->description,
                'date' => date('Y-m-d', $caseData->dateUnix),
                'currency' => 'EUR',
                'language' => 'es',
                'items' => [$lineItem],
            ];
        }

        $lineItem['subtotal'] = $caseData->subtotal;

        return [
            'contactId' => $holdedContactId,
            'desc' => $caseData->description,
            'date' => $caseData->dateUnix,
            'items' => [$lineItem],
        ];
    }

    private function appendPaymentMethodToDescription(string $description, string $paymentMethod): string
    {
        $method = trim($paymentMethod);
        if ('' === $method) {
            return $description;
        }

        return $description . ' [' . $method . ']';
    }

    private function usesApiV2(): bool
    {
        $base = $this->holdedApiBaseUrl;
        if (str_contains($base, 'mock') || str_contains($base, 'invoicing/v1')) {
            return false;
        }

        return str_contains($base, '/v2');
    }

    /**
     * @param array<string, mixed>|null $json
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $json = null): array
    {
        $url = rtrim($this->holdedApiBaseUrl, '/') . $path;
        $options = [
            'headers' => $this->headers(),
            'timeout' => self::TIMEOUT,
        ];
        if (null !== $json) {
            $options['json'] = $json;
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $status = $response->getStatusCode();
            $body = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Holded transport error', [
                'method' => $method,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new HoldedApiException('Timeout o error de red con Holded: ' . $e->getMessage(), 0, $e);
        }

        if ($status >= 400) {
            $this->logger->error('Holded HTTP error', [
                'method' => $method,
                'path' => $path,
                'status' => $status,
                'body' => $body,
            ]);
            throw HoldedApiException::fromResponse($status, $body);
        }

        if ('' === trim($body)) {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new HoldedApiException('Respuesta JSON inválida de Holded.', $status, $e);
        }

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->usesApiV2()) {
            $headers['Authorization'] = 'Bearer ' . $this->holdedApiKey;
        } else {
            $headers['key'] = $this->holdedApiKey;
        }

        return $headers;
    }
}
