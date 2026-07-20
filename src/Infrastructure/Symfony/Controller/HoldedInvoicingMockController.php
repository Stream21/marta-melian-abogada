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
 * Mock de la API de facturación de Holded usada por cobros Stripe/manuales (HoldedPort).
 * Rutas compatibles con HoldedService: /invoicing/v1/invoices.
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
    ) {
        $this->dbPath = $projectDir . '/var/holded_mock_db.json';
        $this->mockKey = $holdedMockKey;
    }

    #[Route(path: '/invoices', name: 'invoices_create', methods: ['POST'])]
    public function createInvoice(Request $request): JsonResponse
    {
        $error = $this->validateKey($request);
        if (null !== $error) {
            return $error;
        }

        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $clientName = trim((string) ($data['contactName'] ?? ''));
        $reference = trim((string) ($data['desc'] ?? ''));
        $lines = $data['lines'] ?? [];

        $total = array_reduce($lines, static function (float $carry, array $item): float {
            $qty = (float) ($item['quantity'] ?? $item['units'] ?? 1);
            $price = (float) ($item['price'] ?? $item['subtotal'] ?? 0);

            return $carry + ($qty * $price);
        }, 0.0);

        $concept = '';
        if ([] !== $lines) {
            $concept = trim((string) ($lines[0]['name'] ?? $lines[0]['desc'] ?? 'Servicios'));
        }

        $db = $this->readDb();
        $id = bin2hex(random_bytes(12));
        $number = 'FAC-' . date('Y') . '-' . str_pad((string) (count($db['paymentInvoices'] ?? []) + 1), 4, '0', \STR_PAD_LEFT);

        $invoice = [
            'id' => $id,
            'number' => $number,
            'contactName' => $clientName,
            'reference' => $reference,
            'concept' => $concept,
            'total' => round($total, 2),
            'paid' => false,
            'createdAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $db['paymentInvoices'][] = $invoice;
        $this->writeDb($db);

        return new JsonResponse(['id' => $id, 'invoiceId' => $id], JsonResponse::HTTP_CREATED);
    }

    #[Route(path: '/invoices/{id}', name: 'invoices_update', methods: ['PATCH'])]
    public function markAsPaid(Request $request, string $id): JsonResponse
    {
        $error = $this->validateKey($request);
        if (null !== $error) {
            return $error;
        }

        $db = $this->readDb();
        $invoice = $this->findPaymentInvoice($db, $id);
        if (null === $invoice) {
            return new JsonResponse(['error' => 'Invoice not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        foreach ($db['paymentInvoices'] as &$stored) {
            if ($stored['id'] === $id) {
                $stored['paid'] = true;
                break;
            }
        }
        unset($stored);
        $this->writeDb($db);

        return new JsonResponse(['id' => $id, 'paid' => true]);
    }

    #[Route(path: '/invoices/{id}/pdf', name: 'invoices_pdf', methods: ['GET'])]
    public function downloadInvoicePdf(Request $request, string $id): Response
    {
        $error = $this->validateKey($request);
        if (null !== $error) {
            return $error;
        }

        $db = $this->readDb();
        $invoice = $this->findPaymentInvoice($db, $id);
        if (null === $invoice) {
            return new JsonResponse(['error' => 'Invoice not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $pdfBinary = $this->pdfGenerator->generate(
            number: (string) $invoice['number'],
            clientName: (string) ($invoice['contactName'] ?? 'Cliente'),
            total: (float) ($invoice['total'] ?? 0),
            reference: (string) ($invoice['reference'] ?? ''),
            concept: (string) ($invoice['concept'] ?? ''),
        );

        return new Response($pdfBinary, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $invoice['number'] . '.pdf"',
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

    /** @return array{contacts: list<mixed>, invoices: list<mixed>, paymentInvoices: list<mixed>} */
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
            'contacts' => $decoded['contacts'] ?? [],
            'invoices' => $decoded['invoices'] ?? [],
            'paymentInvoices' => $decoded['paymentInvoices'] ?? [],
        ];
    }

    /** @param array{contacts: list<mixed>, invoices: list<mixed>, paymentInvoices: list<mixed>} $db */
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

    /** @param array{paymentInvoices: list<mixed>} $db */
    private function findPaymentInvoice(array $db, string $id): ?array
    {
        foreach ($db['paymentInvoices'] ?? [] as $invoice) {
            if (($invoice['id'] ?? '') === $id) {
                return $invoice;
            }
        }

        return null;
    }
}
