<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Invoice;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\InvoiceRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Infrastructure\ApiClient\HoldedApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class PaymentFacadeService
{
    public function __construct(
        private readonly HoldedApiClient $holdedApiClient,
        private readonly ExpedienteRepositoryInterface $expedienteRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Creates an invoice linked to an expediente.
     * Finds or creates a Holded contact using the expediente's clientName and the optional email.
     */
    public function createFromExpediente(
        string $expedienteId,
        string $concepto,
        string $modalidad,
        float $importe,
        string $phone = '',
        string $email = '',
    ): Invoice {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));

        if ($expediente === null) {
            throw new \InvalidArgumentException("Expediente not found: {$expedienteId}");
        }

        $contactId = $this->findOrCreateContact(
            $expediente->clientName(),
            $email,
            $expediente->caseReference(),
        );

        return $this->buildInvoice(
            contactId: $contactId,
            concepto: $concepto,
            modalidad: $modalidad,
            importe: $importe,
            phone: $phone,
            expedienteId: $expedienteId,
        );
    }

    /**
     * Creates a quick invoice for an already-existing Holded contact (from /clientes page).
     */
    public function createFromContact(
        string $contactId,
        string $concepto,
        float $importe,
        string $phone = '',
    ): Invoice {
        return $this->buildInvoice(
            contactId: $contactId,
            concepto: $concepto,
            modalidad: 'honorarios',
            importe: $importe,
            phone: $phone,
            expedienteId: null,
        );
    }

    /**
     * Returns all contacts from the Holded mock API.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listContacts(): array
    {
        return $this->holdedApiClient->listContacts();
    }

    /**
     * Looks up an existing contact by email; creates a new one if none found.
     */
    private function findOrCreateContact(string $name, string $email, string $code = ''): string
    {
        if ($email !== '') {
            foreach ($this->holdedApiClient->listContacts() as $contact) {
                if (isset($contact['email']) && $contact['email'] === $email) {
                    return (string) $contact['id'];
                }
            }
        }

        return $this->holdedApiClient->createContact($name, $email, $code);
    }

    private function buildInvoice(
        string $contactId,
        string $concepto,
        string $modalidad,
        float $importe,
        string $phone,
        ?string $expedienteId,
    ): Invoice {
        $invoiceData = $this->holdedApiClient->createInvoice($contactId, [
            ['desc' => $concepto, 'quantity' => 1, 'price' => $importe],
        ]);

        $holdedId = (string) ($invoiceData['id'] ?? '');
        $numero   = (string) ($invoiceData['number'] ?? '');
        $total    = (float) ($invoiceData['total'] ?? $importe);

        $pdfContent = $this->holdedApiClient->downloadInvoicePdf($holdedId);
        $pdfPath    = $this->savePdf($contactId, $numero, $pdfContent);

        $invoice = new Invoice(
            id: Uuid::v4()->toRfc4122(),
            expedienteId: $expedienteId,
            holdedId: $holdedId,
            numero: $numero,
            concepto: $concepto,
            modalidad: $modalidad,
            fecha: new \DateTimeImmutable(),
            importe: $total,
            estadoHolded: 'draft',
            pdfPath: $pdfPath,
            createdAt: new \DateTimeImmutable(),
        );

        $this->invoiceRepository->save($invoice);

        if ($phone !== '') {
            $this->logger->info(
                \sprintf('[TWILIO-SIM] WhatsApp→%s: Factura %s (%.2f€) generada.', $phone, $numero, $total),
            );
        }

        return $invoice;
    }

    private function savePdf(string $contactId, string $numero, string $content): string
    {
        $relDir  = 'storage/invoices/client_' . $contactId;
        $absDir  = $this->projectDir . '/public/' . $relDir;

        if (!is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }

        $filename = $numero . '.pdf';
        file_put_contents($absDir . '/' . $filename, $content);

        return $relDir . '/' . $filename;
    }
}
