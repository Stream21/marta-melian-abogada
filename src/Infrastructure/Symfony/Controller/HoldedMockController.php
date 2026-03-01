<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/mock/holded/v1', name: 'api_mock_holded_')]
final class HoldedMockController extends AbstractController
{
    private string $dbPath;
    private string $mockKey;

    public function __construct(string $projectDir, string $holdedMockKey)
    {
        $this->dbPath  = $projectDir . '/var/holded_mock_db.json';
        $this->mockKey = $holdedMockKey;
    }

    // -------------------------------------------------------------------------
    // Contacts
    // -------------------------------------------------------------------------

    #[Route(path: '/contacts', name: 'contacts_create', methods: ['POST'])]
    public function createContact(Request $request): JsonResponse
    {
        $error = $this->validateKey($request);
        if ($error !== null) {
            return $error;
        }

        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $name  = trim((string) ($data['name']  ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $code  = trim((string) ($data['code']  ?? ''));

        if ($name === '' || $email === '') {
            return new JsonResponse(['error' => 'name and email are required'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $db = $this->readDb();
        $id = $this->generateHexId();

        $contact = [
            'id'    => $id,
            'name'  => $name,
            'email' => $email,
            'code'  => $code,
        ];

        $db['contacts'][] = $contact;
        $this->writeDb($db);

        return new JsonResponse($contact, JsonResponse::HTTP_CREATED);
    }

    #[Route(path: '/contacts', name: 'contacts_list', methods: ['GET'])]
    public function listContacts(Request $request): JsonResponse
    {
        $error = $this->validateKey($request);
        if ($error !== null) {
            return $error;
        }

        $db = $this->readDb();

        return new JsonResponse($db['contacts'] ?? []);
    }

    // -------------------------------------------------------------------------
    // Invoices
    // -------------------------------------------------------------------------

    #[Route(path: '/documents/invoice', name: 'invoice_create', methods: ['POST'])]
    public function createInvoice(Request $request): JsonResponse
    {
        $error = $this->validateKey($request);
        if ($error !== null) {
            return $error;
        }

        $data      = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $contactId = trim((string) ($data['contactId'] ?? ''));
        $items     = $data['items'] ?? [];

        if ($contactId === '') {
            return new JsonResponse(['error' => 'contactId is required'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $db = $this->readDb();

        $contact = $this->findContact($db, $contactId);
        if ($contact === null) {
            return new JsonResponse(['error' => 'Contact not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $total = array_reduce($items, static function (float $carry, array $item): float {
            $qty   = (float) ($item['units'] ?? $item['qty'] ?? 1);
            $price = (float) ($item['price'] ?? $item['subtotal'] ?? 0);
            return $carry + ($qty * $price);
        }, 0.0);

        $id     = $this->generateHexId();
        $number = 'INV-' . date('Y') . '-' . str_pad((string) (count($db['invoices'] ?? []) + 1), 4, '0', \STR_PAD_LEFT);

        $invoice = [
            'id'        => $id,
            'contactId' => $contactId,
            'number'    => $number,
            'total'     => round($total, 2),
            'items'     => $items,
            'createdAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $db['invoices'][] = $invoice;
        $this->writeDb($db);

        return new JsonResponse([
            'id'     => $id,
            'number' => $number,
            'total'  => $invoice['total'],
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route(path: '/documents/invoice/{id}/pdf', name: 'invoice_pdf', methods: ['GET'])]
    public function downloadInvoicePdf(Request $request, string $id): JsonResponse
    {
        $error = $this->validateKey($request);
        if ($error !== null) {
            return $error;
        }

        $db      = $this->readDb();
        $invoice = $this->findInvoice($db, $id);

        if ($invoice === null) {
            return new JsonResponse(['error' => 'Invoice not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $contact = $this->findContact($db, $invoice['contactId']);
        $clientName = $contact['name'] ?? 'Unknown';

        $pdfBinary = $this->generatePdf(
            number: $invoice['number'],
            clientName: $clientName,
            total: (float) $invoice['total'],
        );

        return new JsonResponse(['base64' => base64_encode($pdfBinary)]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function validateKey(Request $request): ?JsonResponse
    {
        $key = $request->headers->get('key') ?? $request->headers->get('authorization') ?? '';
        if ($key !== $this->mockKey) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return null;
    }

    private function readDb(): array
    {
        if (!file_exists($this->dbPath)) {
            return ['contacts' => [], 'invoices' => []];
        }

        $raw = file_get_contents($this->dbPath);
        if ($raw === false || $raw === '') {
            return ['contacts' => [], 'invoices' => []];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['contacts' => [], 'invoices' => []];
    }

    private function writeDb(array $db): void
    {
        $dir = dirname($this->dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $this->dbPath,
            json_encode($db, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE),
        );
    }

    private function generateHexId(): string
    {
        return bin2hex(random_bytes(12));
    }

    /** @param array<string, mixed> $db */
    private function findContact(array $db, string $id): ?array
    {
        foreach ($db['contacts'] ?? [] as $contact) {
            if ($contact['id'] === $id) {
                return $contact;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $db */
    private function findInvoice(array $db, string $id): ?array
    {
        foreach ($db['invoices'] ?? [] as $invoice) {
            if ($invoice['id'] === $id) {
                return $invoice;
            }
        }

        return null;
    }

    /**
     * Generates a minimal but valid PDF 1.4 document in pure PHP.
     * Tracks byte offsets manually to build a correct cross-reference table.
     */
    private function generatePdf(string $number, string $clientName, float $total): string
    {
        $line1 = 'FACTURA: ' . $number;
        $line2 = 'CLIENTE: ' . $clientName;
        $line3 = 'IMPORTE: ' . number_format($total, 2, '.', '') . ' EUR';

        // Build content stream
        $stream  = "BT\n";
        $stream .= "/F1 14 Tf\n";
        $stream .= "50 750 Td\n";
        $stream .= '(' . $this->escapePdfString($line1) . ") Tj\n";
        $stream .= "0 -25 Td\n";
        $stream .= '(' . $this->escapePdfString($line2) . ") Tj\n";
        $stream .= "0 -25 Td\n";
        $stream .= '(' . $this->escapePdfString($line3) . ") Tj\n";
        $stream .= "ET\n";

        $streamLen = strlen($stream);

        // Build PDF objects
        $objects = [];

        // Object 1: Catalog
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // Object 2: Pages
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // Object 3: Page
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";

        // Object 4: Content stream
        $objects[4] = "4 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}endstream\nendobj\n";

        // Object 5: Font
        $objects[5] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n";

        // Assemble PDF body and track offsets
        $header  = "%PDF-1.4\n";
        $body    = '';
        $offsets = [];

        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($header) + strlen($body);
            $body .= $obj;
        }

        $xrefOffset = strlen($header) + strlen($body);

        // Cross-reference table
        $xref  = "xref\n";
        $xref .= '0 ' . (count($objects) + 1) . "\n";
        $xref .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $xref .= str_pad((string) $offset, 10, '0', \STR_PAD_LEFT) . " 00000 n \n";
        }

        $trailer = "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return $header . $body . $xref . $trailer;
    }

    private function escapePdfString(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
