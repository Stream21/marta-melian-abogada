<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\ApiClient;

use App\Application\DTO\Holded\ClienteHoldedData;
use App\Application\DTO\Holded\ExpedienteInvoiceData;
use App\Infrastructure\ApiClient\HoldedService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class HoldedServiceTest extends TestCase
{
    public function testV2UsesBearerAndSnakeCasePaths(): void
    {
        $requests = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

            if (str_contains($url, '/contacts') && 'GET' === $method) {
                return new MockResponse('{"items":[]}', ['http_code' => 200]);
            }
            if (str_contains($url, '/contacts') && 'POST' === $method) {
                return new MockResponse('{"id":"contact-v2-1"}', ['http_code' => 201]);
            }
            if (str_contains($url, '/invoices') && 'POST' === $method && !str_contains($url, '/payments')) {
                return new MockResponse('{"id":"inv-v2-1"}', ['http_code' => 201]);
            }
            if (str_contains($url, '/payments')) {
                return new MockResponse('{}', ['http_code' => 201]);
            }

            return new MockResponse('{}', ['http_code' => 200]);
        });

        $service = new HoldedService(
            $client,
            'pat_test_token',
            'https://api.holded.com/api/v2',
            new NullLogger(),
        );

        $contactId = $service->findOrCreateContact(new ClienteHoldedData(
            name: 'Test',
            email: 't@example.com',
            documentNumber: '12345678Z',
            address: 'Calle 1',
            city: 'Las Palmas',
            postalCode: '35001',
            countryCode: 'ES',
            phone: '',
        ));
        self::assertSame('contact-v2-1', $contactId);

        $invoiceId = $service->createInvoice($contactId, new ExpedienteInvoiceData(
            description: 'Expediente 1',
            totalWithTax: 100.0,
            itemName: 'Honorarios',
            subtotal: 100.0,
            taxKey: 's_iva_exento',
            dateUnix: 1710000000,
            taxes: ['s_iva_exento'],
        ));
        self::assertSame('inv-v2-1', $invoiceId);

        $service->recordPayment($invoiceId, 50.0, 'Cuota 1', 'STRIPE');

        self::assertSame('GET', $requests[0]['method']);
        self::assertStringContainsString('/contacts?code=12345678Z', $requests[0]['url']);
        self::assertSame(
            'Bearer pat_test_token',
            $this->headerValue($requests[0]['options'], 'Authorization'),
        );

        self::assertSame('POST', $requests[1]['method']);
        $contactBody = json_decode($requests[1]['options']['body'] ?? '{}', true);
        self::assertSame(['client'], $contactBody['type'] ?? null);
        self::assertTrue($contactBody['is_person'] ?? false);
        self::assertSame('35001', $contactBody['bill_address']['postal_code'] ?? null);

        $invoiceBody = json_decode($requests[2]['options']['body'] ?? '{}', true);
        self::assertSame('contact-v2-1', $invoiceBody['contact_id'] ?? null);
        self::assertSame(date('Y-m-d', 1710000000), $invoiceBody['date'] ?? null);
        self::assertSame(100.0, $invoiceBody['items'][0]['price'] ?? null);

        self::assertStringContainsString('/invoices/inv-v2-1/payments', $requests[3]['url']);
        $payBody = json_decode($requests[3]['options']['body'] ?? '{}', true);
        self::assertSame('50.00', $payBody['amount'] ?? null);
        self::assertStringContainsString('STRIPE', (string) ($payBody['description'] ?? ''));
    }

    public function testEnvPrefixIsolatesContactsAndInvoiceNumbers(): void
    {
        $requests = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

            if (str_contains($url, '/contacts/') && 'GET' === $method) {
                return new MockResponse('{"error":"not found"}', ['http_code' => 404]);
            }
            if (str_contains($url, '/contacts') && 'GET' === $method) {
                return new MockResponse('{"items":[]}', ['http_code' => 200]);
            }
            if (str_contains($url, '/contacts') && 'POST' === $method) {
                return new MockResponse('{"id":"contact-stg-1"}', ['http_code' => 201]);
            }
            if (str_contains($url, '/invoices') && 'POST' === $method) {
                return new MockResponse('{"id":"inv-stg-1"}', ['http_code' => 201]);
            }

            return new MockResponse('{}', ['http_code' => 200]);
        });

        $service = new HoldedService(
            $client,
            'pat_test_token',
            'https://api.holded.com/api/v2',
            new NullLogger(),
            'stg',
        );

        $contactId = $service->findOrCreateContact(new ClienteHoldedData(
            name: 'Cliente Demo',
            email: 'd@example.com',
            documentNumber: '12345678Z',
            existingHoldedContactId: 'deleted-id',
        ));
        self::assertSame('contact-stg-1', $contactId);

        $invoiceId = $service->createInvoice($contactId, new ExpedienteInvoiceData(
            description: 'Expediente EXP-1',
            totalWithTax: 100.0,
            itemName: 'Honorarios',
            subtotal: 100.0,
            taxKey: 's_iva_exento',
            dateUnix: 1710000000,
            taxes: ['s_iva_exento'],
            numberKey: 'EXP-2026/0001',
        ));
        self::assertSame('inv-stg-1', $invoiceId);

        self::assertStringContainsString('code=STG-12345678Z', $requests[1]['url']);
        $contactBody = json_decode($requests[2]['options']['body'] ?? '{}', true);
        self::assertSame('STG-12345678Z', $contactBody['code'] ?? null);
        self::assertSame('[STG] Cliente Demo', $contactBody['name'] ?? null);

        $invoiceBody = json_decode($requests[3]['options']['body'] ?? '{}', true);
        self::assertSame('[STG] Expediente EXP-1', $invoiceBody['description'] ?? null);
        self::assertSame(['STG'], $invoiceBody['tags'] ?? null);
        self::assertIsString($invoiceBody['number'] ?? null);
        self::assertStringStartsWith('STG-EXP-2026-0001-', (string) ($invoiceBody['number'] ?? ''));
    }

    public function testFindOrCreateContactReusesHoldedIdWhenFoundByDocument(): void
    {
        $requests = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

            return new MockResponse(
                '{"items":[{"id":"existing-holded-42","code":"12345678Z","name":"Ya Existe"}]}',
                ['http_code' => 200],
            );
        });

        $service = new HoldedService(
            $client,
            'pat_test_token',
            'https://api.holded.com/api/v2',
            new NullLogger(),
        );

        $contactId = $service->findOrCreateContact(new ClienteHoldedData(
            name: 'Test',
            email: 't@example.com',
            documentNumber: '12345678Z',
        ));

        self::assertSame('existing-holded-42', $contactId);
        self::assertCount(1, $requests);
        self::assertSame('GET', $requests[0]['method']);
        self::assertStringContainsString('code=12345678Z', $requests[0]['url']);
    }

    public function testFindOrCreateContactSkipsLookupWhenLocalIdPresent(): void
    {
        $requests = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

            if (str_contains($url, '/contacts/') && 'GET' === $method) {
                return new MockResponse('{"id":"local-id-9","code":"12345678Z"}', ['http_code' => 200]);
            }

            return new MockResponse('{"id":"should-not-create"}', ['http_code' => 201]);
        });

        $service = new HoldedService(
            $client,
            'pat_test_token',
            'https://api.holded.com/api/v2',
            new NullLogger(),
        );

        $contactId = $service->findOrCreateContact(new ClienteHoldedData(
            name: 'Test',
            email: 't@example.com',
            documentNumber: '12345678Z',
            existingHoldedContactId: 'local-id-9',
        ));

        self::assertSame('local-id-9', $contactId);
        self::assertCount(1, $requests);
        self::assertSame('GET', $requests[0]['method']);
        self::assertStringContainsString('/contacts/local-id-9', $requests[0]['url']);
    }

    public function testFindOrCreateContactMatchesDocumentIgnoringSpacesAndCase(): void
    {
        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse(
            '[{"id":"mock-1","code":"x1234567l"}]',
            ['http_code' => 200],
        ));

        $service = new HoldedService(
            $client,
            'dev-mock-key-123',
            'http://nginx/api/mock/holded/invoicing/v1',
            new NullLogger(),
        );

        $contactId = $service->findOrCreateContact(new ClienteHoldedData(
            name: 'Test',
            email: 't@example.com',
            documentNumber: 'X 1234567 L',
        ));

        self::assertSame('mock-1', $contactId);
    }

    public function testLegacyMockUsesKeyHeaderAndV1Paths(): void
    {
        $requests = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

            return new MockResponse('{"id":"legacy-1"}', ['http_code' => 201]);
        });

        $service = new HoldedService(
            $client,
            'dev-mock-key-123',
            'http://nginx/api/mock/holded/invoicing/v1',
            new NullLogger(),
        );

        $service->createInvoice('c1', new ExpedienteInvoiceData(
            description: 'Desc',
            totalWithTax: 10.0,
            itemName: 'Item',
            subtotal: 10.0,
            taxKey: 'exento',
            dateUnix: 1710000000,
            taxes: ['exento'],
        ));

        self::assertSame('dev-mock-key-123', $this->headerValue($requests[0]['options'], 'key'));
        self::assertStringContainsString('/documents/invoice', $requests[0]['url']);
        $body = json_decode($requests[0]['options']['body'] ?? '{}', true);
        self::assertSame('c1', $body['contactId'] ?? null);
        self::assertSame(1710000000, $body['date'] ?? null);
        self::assertSame(10.0, $body['items'][0]['subtotal'] ?? null);
    }

    public function testListContactsReadsItemsWrapper(): void
    {
        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse(
            '{"items":[{"id":"a","name":"Uno"}]}',
            ['http_code' => 200],
        ));

        $service = new HoldedService(
            $client,
            'pat_x',
            'https://api.holded.com/api/v2',
            new NullLogger(),
        );

        $list = $service->listContacts();
        self::assertCount(1, $list);
        self::assertSame('a', $list[0]['id']);
    }

    /** @param array<string, mixed> $options */
    private function headerValue(array $options, string $name): ?string
    {
        $key = strtolower($name);
        $normalized = $options['normalized_headers'] ?? null;
        if (is_array($normalized) && isset($normalized[$key][0])) {
            return $this->stripHeaderName((string) $normalized[$key][0], $name);
        }

        $headers = $options['headers'] ?? [];
        if (!is_array($headers)) {
            return null;
        }

        foreach ($headers as $headerName => $value) {
            if (is_string($headerName) && strtolower($headerName) === $key) {
                $raw = is_array($value) ? (string) ($value[0] ?? '') : (string) $value;

                return $this->stripHeaderName($raw, $name);
            }
            if (is_int($headerName) && is_string($value) && str_starts_with(strtolower($value), $key . ':')) {
                return $this->stripHeaderName($value, $name);
            }
        }

        return null;
    }

    private function stripHeaderName(string $raw, string $name): string
    {
        $prefix = strtolower($name) . ':';
        if (str_starts_with(strtolower($raw), $prefix)) {
            return trim(substr($raw, strlen($name) + 1));
        }

        return trim($raw);
    }
}
