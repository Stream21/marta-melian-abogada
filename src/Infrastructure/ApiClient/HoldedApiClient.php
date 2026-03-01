<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiClient;

use App\Domain\Exception\HoldedApiException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Standalone HTTP client for the Holded mock API.
 * Unlike HoldedService (which implements HoldedPort for the real Holded API),
 * this client targets the mock server and exposes richer contact/invoice operations
 * needed by the billing module.
 */
final class HoldedApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $holdedApiUrl,
        private readonly string $holdedMockKey,
    ) {
    }

    /**
     * Creates a contact and returns its 24-char hex ID.
     */
    public function createContact(string $name, string $email, string $code = ''): string
    {
        $response = $this->httpClient->request('POST', $this->holdedApiUrl . '/v1/contacts', [
            'headers' => $this->headers(),
            'json' => [
                'name'  => $name,
                'email' => $email,
                'code'  => $code,
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            throw HoldedApiException::fromResponse($statusCode, $response->getContent(false));
        }

        $data = $response->toArray();

        return (string) ($data['id'] ?? '');
    }

    /**
     * Returns a single contact by ID, or null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function getContact(string $id): ?array
    {
        $contacts = $this->listContacts();

        foreach ($contacts as $contact) {
            if (isset($contact['id']) && $contact['id'] === $id) {
                return $contact;
            }
        }

        return null;
    }

    /**
     * Returns all contacts from the mock database.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listContacts(): array
    {
        $response = $this->httpClient->request('GET', $this->holdedApiUrl . '/v1/contacts', [
            'headers' => $this->headers(),
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            throw HoldedApiException::fromResponse($statusCode, $response->getContent(false));
        }

        return $response->toArray();
    }

    /**
     * Creates an invoice for the given contact and returns {id, number, total}.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    public function createInvoice(string $contactId, array $items): array
    {
        $response = $this->httpClient->request('POST', $this->holdedApiUrl . '/v1/documents/invoice', [
            'headers' => $this->headers(),
            'json' => [
                'contactId' => $contactId,
                'items'     => $items,
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            throw HoldedApiException::fromResponse($statusCode, $response->getContent(false));
        }

        return $response->toArray();
    }

    /**
     * Downloads the PDF for the given invoice and returns the decoded binary content.
     */
    public function downloadInvoicePdf(string $invoiceId): string
    {
        $response = $this->httpClient->request('GET', $this->holdedApiUrl . '/v1/documents/invoice/' . $invoiceId . '/pdf', [
            'headers' => $this->headers(),
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            throw HoldedApiException::fromResponse($statusCode, $response->getContent(false));
        }

        $data = $response->toArray();

        if (!isset($data['base64'])) {
            throw new HoldedApiException('PDF response missing base64 field');
        }

        $decoded = base64_decode((string) $data['base64'], true);

        if ($decoded === false) {
            throw new HoldedApiException('Failed to decode base64 PDF content');
        }

        return $decoded;
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return [
            'key'          => $this->holdedMockKey,
            'Content-Type' => 'application/json',
        ];
    }
}
