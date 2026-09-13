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
 *
 * Con HOLDED_ENV_PREFIX (DEV/STG) aísla contactos y números de factura entre entornos
 * que compartan la misma cuenta demo, y evita reutilizar la serie automática tras borrados.
 */
final class HoldedService implements HoldedPort
{
    private const TIMEOUT = 15.0;

    private string $envPrefix;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $holdedApiKey,
        private string $holdedApiBaseUrl,
        private LoggerInterface $logger,
        string $holdedEnvPrefix = '',
    ) {
        $this->envPrefix = strtoupper(trim($holdedEnvPrefix));
    }

    public function findOrCreateContact(ClienteHoldedData $clientData): string
    {
        $existing = trim((string) ($clientData->existingHoldedContactId ?? ''));
        if ('' !== $existing) {
            if ($this->contactExists($existing)) {
                return $existing;
            }
            $this->logger->warning('Holded contact id local ya no existe; se recreará.', [
                'holdedContactId' => $existing,
            ]);
        }

        $documentNumber = $this->prefixedDocumentCode(trim($clientData->documentNumber));
        if ('' !== $documentNumber) {
            $foundId = $this->findContactIdByDocument($documentNumber);
            if (null !== $foundId) {
                return $foundId;
            }
        }

        $data = $this->request('POST', '/contacts', $this->buildContactPayload($clientData, $documentNumber));

        $id = (string) ($data['id'] ?? '');
        if ('' === $id) {
            throw new HoldedApiException('Holded no devolvió id de contacto.');
        }

        return $id;
    }

    private function contactExists(string $contactId): bool
    {
        // El mock v1 no expone GET /contacts/{id}; confiamos en el id local.
        if (!$this->usesApiV2()) {
            return true;
        }

        try {
            $this->request('GET', '/contacts/' . rawurlencode($contactId));

            return true;
        } catch (HoldedApiException $e) {
            if (404 === $e->getStatusCode()) {
                return false;
            }
            // Si la API no permite GET por id, asumimos que el id local sigue siendo válido.
            if ($e->getStatusCode() >= 400 && $e->getStatusCode() < 500) {
                return true;
            }

            throw $e;
        }
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

    private function prefixedDocumentCode(string $documentNumber): string
    {
        $normalized = $this->normalizeDocumentCode($documentNumber);
        if ('' === $normalized || '' === $this->envPrefix) {
            return $normalized;
        }

        $prefix = $this->envPrefix . '-';
        if (str_starts_with($normalized, $prefix)) {
            return $normalized;
        }

        return $prefix . $normalized;
    }

    private function prefixedDisplayName(string $name): string
    {
        $trimmed = trim($name);
        if ('' === $this->envPrefix || '' === $trimmed) {
            return $trimmed;
        }

        $tag = '[' . $this->envPrefix . ']';
        if (str_starts_with($trimmed, $tag)) {
            return $trimmed;
        }

        return $tag . ' ' . $trimmed;
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
    private function buildContactPayload(ClienteHoldedData $clientData, string $documentNumber): array
    {
        $name = $this->prefixedDisplayName($clientData->name);

        if ($this->usesApiV2()) {
            $payload = [
                'name' => $name,
                'email' => $clientData->email,
                'code' => $documentNumber,
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
            'name' => $name,
            'email' => $clientData->email,
            'code' => $documentNumber,
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
        $description = $this->prefixedDescription($caseData->description);
        $documentNumber = $this->resolveInvoiceDocumentNumber($caseData);

        if ($this->usesApiV2()) {
            $lineItem['type'] = 'service';
            $lineItem['price'] = $caseData->subtotal;

            $payload = [
                'contact_id' => $holdedContactId,
                'description' => $description,
                'date' => date('Y-m-d', $caseData->dateUnix),
                'currency' => 'EUR',
                'language' => 'es',
                'items' => [$lineItem],
            ];
            if (null !== $documentNumber) {
                // Número explícito: no depende de la serie automática (borra/recrea en demo sin pisar).
                $payload['number'] = $documentNumber;
            }
            if ('' !== $this->envPrefix) {
                $payload['tags'] = [$this->envPrefix];
            }

            return $payload;
        }

        $lineItem['subtotal'] = $caseData->subtotal;

        $payload = [
            'contactId' => $holdedContactId,
            'desc' => $description,
            'date' => $caseData->dateUnix,
            'items' => [$lineItem],
        ];
        if (null !== $documentNumber) {
            $payload['invoiceNum'] = $documentNumber;
            $payload['number'] = $documentNumber;
        }

        return $payload;
    }

    private function resolveInvoiceDocumentNumber(ExpedienteInvoiceData $caseData): ?string
    {
        $explicit = trim((string) ($caseData->documentNumber ?? ''));
        if ('' !== $explicit) {
            return $explicit;
        }

        if ('' === $this->envPrefix) {
            return null;
        }

        $key = trim((string) ($caseData->numberKey ?? ''));
        if ('' === $key) {
            $key = 'DOC';
        }
        $safeKey = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $key) ?? 'DOC');
        $safeKey = trim($safeKey, '-');
        // Timestamp + sufijo corto: tras borrar en Holded, el siguiente sync crea otro número.
        $suffix = (new \DateTimeImmutable('now'))->format('YmdHis') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);

        return sprintf('%s-%s-%s', $this->envPrefix, $safeKey, $suffix);
    }

    private function prefixedDescription(string $description): string
    {
        $trimmed = trim($description);
        if ('' === $this->envPrefix || '' === $trimmed) {
            return $trimmed;
        }

        $tag = '[' . $this->envPrefix . ']';
        if (str_starts_with($trimmed, $tag)) {
            return $trimmed;
        }

        return $tag . ' ' . $trimmed;
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
