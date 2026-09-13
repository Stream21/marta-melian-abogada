<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Infrastructure\Mock\HoldedMockPdfGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Mock oficial Holded Invoicing API v1 (Canarias, régimen exento por defecto).
 */
#[Route(path: '/api/mock/holded/invoicing/v1', name: 'api_mock_holded_invoicing_')]
final class HoldedInvoicingMockController extends AbstractController
{
    private string $dbPath;
    private string $mockKey;

    public function __construct(
        string $projectDir,
        string $holdedMockKey,
        private HoldedMockPdfGenerator $pdfGenerator,
        private float $holdedTaxPercent = 0.0,
        private string $holdedTaxKey = 'exento',
    ) {
        $this->dbPath = $projectDir . '/var/holded_mock_db.json';
        $this->mockKey = $holdedMockKey;
    }

    #[Route(path: '/contacts', name: 'contacts_create', methods: ['POST'])]
    public function createContact(Request $request): JsonResponse
    {
        $error = $this->validateKey($request);
        if (null !== $error) {
            return $error;
        }

        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));

        if ('' === $name || '' === $email) {
            return new JsonResponse(['error' => 'name and email are required'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $db = $this->readDb();

        if ('' !== $code) {
            foreach ($db['contacts'] as $existing) {
                if (($existing['code'] ?? '') === $code) {
                    return new JsonResponse($existing, JsonResponse::HTTP_OK);
                }
            }
        }

        $id = $this->generateHexId();
        $contact = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'code' => $code,
            'type' => (string) ($data['type'] ?? 'client'),
            'isperson' => (bool) ($data['isperson'] ?? true),
            'billAddress' => $data['billAddress'] ?? [],
            'phone' => (string) ($data['phone'] ?? ''),
        ];

        $db['contacts'][] = $contact;
        $this->writeDb($db);

        return new JsonResponse($contact, JsonResponse::HTTP_CREATED);
    }

    #[Route(path: '/contacts', name: 'contacts_list', methods: ['GET'])]
    public function listContacts(Request $request): JsonResponse
    {
        $error = $this->validateKey($request);
        if (null !== $error) {
            return $error;
        }

        $db = $this->readDb();
        $contacts = $db['contacts'];
        $codeFilter = trim((string) $request->query->get('code', ''));
        if ('' !== $codeFilter) {
            $normalized = strtoupper(preg_replace('/\s+/', '', $codeFilter) ?? '');
            $contacts = array_values(array_filter(
                $contacts,
                static function (array $contact) use ($normalized): bool {
                    $code = strtoupper(preg_replace('/\s+/', '', (string) ($contact['code'] ?? '')) ?? '');

                    return '' !== $code && $code === $normalized;
                },
            ));
        }

        return new JsonResponse($contacts);
    }

    #[Route(path: '/documents/invoice', name: 'invoice_create', methods: ['POST'])]
    public function createInvoice(Request $request): JsonResponse
    {
        $error = $this->validateKey($request);
        if (null !== $error) {
            return $error;
        }

        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $contactId = trim((string) ($data['contactId'] ?? ''));
        $items = $data['items'] ?? [];
        $desc = trim((string) ($data['desc'] ?? ''));
        $date = (int) ($data['date'] ?? time());

        if ('' === $contactId) {
            return new JsonResponse(['error' => 'contactId is required'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $db = $this->readDb();
        $contact = $this->findContact($db, $contactId);
        if (null === $contact) {
            return new JsonResponse(['error' => 'Contact not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $subtotal = 0.0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $units = (float) ($item['units'] ?? $item['qty'] ?? 1);
            $lineSub = (float) ($item['subtotal'] ?? $item['price'] ?? 0);
            $subtotal += $units * $lineSub;
        }
        $subtotal = round($subtotal, 2);
        $taxAmount = round($subtotal * ($this->holdedTaxPercent / 100), 2);
        $total = round($subtotal + $taxAmount, 2);

        $id = $this->generateHexId();
        $customNumber = trim((string) ($data['number'] ?? $data['invoiceNum'] ?? ''));
        $number = '' !== $customNumber
            ? $customNumber
            : 'FAC-' . date('Y') . '-' . str_pad((string) (count($db['invoices']) + 1), 4, '0', \STR_PAD_LEFT);

        $invoice = [
            'id' => $id,
            'contactId' => $contactId,
            'number' => $number,
            'desc' => $desc,
            'date' => $date,
            'items' => $items,
            'subtotal' => $subtotal,
            'taxPercent' => $this->holdedTaxPercent,
            'taxKey' => $this->holdedTaxKey,
            'taxAmount' => $taxAmount,
            'total' => $total,
            'paidAmount' => 0.0,
            'paid' => false,
            'payments' => [],
            'createdAt' => (new \DateTimeImmutable('now', new \DateTimeZone('Atlantic/Canary')))->format(\DateTimeInterface::ATOM),
        ];

        $db['invoices'][] = $invoice;
        $this->writeDb($db);

        return new JsonResponse([
            'id' => $id,
            'invoiceId' => $id,
            'number' => $number,
            'total' => $total,
            'subtotal' => $subtotal,
            'taxAmount' => $taxAmount,
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route(path: '/documents/invoice/{id}/pay', name: 'invoice_pay', methods: ['POST'])]
    public function payInvoice(Request $request, string $id): JsonResponse
    {
        $error = $this->validateKey($request);
        if (null !== $error) {
            return $error;
        }

        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $desc = trim((string) ($data['desc'] ?? ''));
        $date = (int) ($data['date'] ?? time());
        $method = trim((string) ($data['paymentMethod'] ?? ''));

        if ($amount <= 0) {
            return new JsonResponse(['error' => 'amount must be > 0'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $db = $this->readDb();
        $found = false;
        foreach ($db['invoices'] as &$invoice) {
            if (($invoice['id'] ?? '') !== $id) {
                continue;
            }
            $found = true;
            $paidAmount = round((float) ($invoice['paidAmount'] ?? 0) + $amount, 2);
            $invoice['paidAmount'] = $paidAmount;
            $invoice['paid'] = $paidAmount + 0.009 >= (float) ($invoice['total'] ?? 0);
            $invoice['payments'][] = [
                'date' => $date,
                'amount' => $amount,
                'desc' => $desc,
                'paymentMethod' => $method,
            ];
            break;
        }
        unset($invoice);

        if (!$found) {
            return new JsonResponse(['error' => 'Invoice not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->writeDb($db);
        $invoice = $this->findInvoice($db, $id);

        return new JsonResponse([
            'id' => $id,
            'paidAmount' => $invoice['paidAmount'] ?? $amount,
            'paid' => (bool) ($invoice['paid'] ?? false),
        ]);
    }

    #[Route(path: '/documents/invoice/{id}/pdf', name: 'invoice_pdf', methods: ['GET'])]
    public function downloadInvoicePdf(Request $request, string $id): Response
    {
        $error = $this->validateKey($request);
        if (null !== $error) {
            return $error;
        }

        $db = $this->readDb();
        $invoice = $this->findInvoice($db, $id);
        if (null === $invoice) {
            return new JsonResponse(['error' => 'Invoice not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $contact = $this->findContact($db, (string) ($invoice['contactId'] ?? ''));
        $clientName = (string) ($contact['name'] ?? 'Cliente');

        $pdfBinary = $this->pdfGenerator->generate(
            number: (string) ($invoice['number'] ?? $id),
            clientName: $clientName,
            total: (float) ($invoice['total'] ?? 0),
            reference: (string) ($invoice['desc'] ?? ''),
            concept: $this->firstItemName($invoice),
            subtotal: isset($invoice['subtotal']) ? (float) $invoice['subtotal'] : null,
            taxAmount: isset($invoice['taxAmount']) ? (float) $invoice['taxAmount'] : null,
            taxPercent: (float) ($invoice['taxPercent'] ?? $this->holdedTaxPercent),
        );

        $format = $request->query->get('format', 'binary');
        if ('json' === $format || '1' === $request->headers->get('X-Holded-Pdf-Json')) {
            return new JsonResponse([
                'status' => 1,
                'data' => base64_encode($pdfBinary),
                'base64' => base64_encode($pdfBinary),
            ]);
        }

        return new Response($pdfBinary, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . ($invoice['number'] ?? 'factura') . '.pdf"',
        ]);
    }

    private function validateKey(Request $request): ?JsonResponse
    {
        $key = $request->headers->get('key') ?? $request->headers->get('authorization') ?? '';
        if ($key !== $this->mockKey) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return null;
    }

    /**
     * @return array{contacts: list<array<string, mixed>>, invoices: list<array<string, mixed>>, paymentInvoices: list<array<string, mixed>>}
     */
    private function readDb(): array
    {
        if (!file_exists($this->dbPath)) {
            return ['contacts' => [], 'invoices' => [], 'paymentInvoices' => []];
        }

        $raw = file_get_contents($this->dbPath);
        if (false === $raw || '' === $raw) {
            return ['contacts' => [], 'invoices' => [], 'paymentInvoices' => []];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return ['contacts' => [], 'invoices' => [], 'paymentInvoices' => []];
        }

        return [
            'contacts' => array_values($decoded['contacts'] ?? []),
            'invoices' => array_values($decoded['invoices'] ?? []),
            'paymentInvoices' => array_values($decoded['paymentInvoices'] ?? []),
        ];
    }

    /** @param array{contacts: list<mixed>, invoices: list<mixed>, paymentInvoices: list<mixed>} $db */
    private function writeDb(array $db): void
    {
        $dir = dirname($this->dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $this->dbPath,
            json_encode($db, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE),
        );
        @chmod($this->dbPath, 0664);
    }

    private function generateHexId(): string
    {
        return bin2hex(random_bytes(12));
    }

    /** @param array{contacts: list<array<string, mixed>>} $db */
    private function findContact(array $db, string $id): ?array
    {
        foreach ($db['contacts'] as $contact) {
            if (($contact['id'] ?? '') === $id) {
                return $contact;
            }
        }

        return null;
    }

    /** @param array{invoices: list<array<string, mixed>>} $db */
    private function findInvoice(array $db, string $id): ?array
    {
        foreach ($db['invoices'] as $invoice) {
            if (($invoice['id'] ?? '') === $id) {
                return $invoice;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $invoice */
    private function firstItemName(array $invoice): string
    {
        $items = $invoice['items'] ?? [];
        if (!is_array($items) || [] === $items) {
            return 'Servicios legales';
        }
        $first = $items[0] ?? [];

        return (string) ($first['name'] ?? $first['desc'] ?? 'Servicios legales');
    }
}
