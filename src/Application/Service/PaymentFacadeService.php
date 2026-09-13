<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Holded\ClienteHoldedData;
use App\Application\DTO\Holded\ExpedienteInvoiceData;
use App\Application\Port\HoldedPort;
use App\Domain\Entity\Invoice;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\InvoiceRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Módulo facturación legacy (UI ModoHolded): usa el mismo HoldedPort unificado.
 */
final class PaymentFacadeService
{
    public function __construct(
        private readonly HoldedPort $holdedPort,
        private readonly ExpedienteRepositoryInterface $expedienteRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly IgicInvoiceCalculator $igicCalculator,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
    ) {
    }

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

        $contactId = $this->holdedPort->findOrCreateContact(new ClienteHoldedData(
            name: $expediente->clientName(),
            email: $email !== '' ? $email : 'contacto+' . $expedienteId . '@oportunidad.bufete.local',
            documentNumber: $expediente->caseReference() !== '' ? $expediente->caseReference() : $expediente->numero(),
        ));

        return $this->buildInvoice(
            contactId: $contactId,
            concepto: $concepto,
            modalidad: $modalidad,
            importe: $importe,
            phone: $phone,
            expedienteId: $expedienteId,
        );
    }

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
     * @return list<array<string, mixed>>
     */
    public function listContacts(): array
    {
        return $this->holdedPort->listContacts();
    }

    private function buildInvoice(
        string $contactId,
        string $concepto,
        string $modalidad,
        float $importe,
        string $phone,
        ?string $expedienteId,
    ): Invoice {
        $breakdown = $this->igicCalculator->fromTotalWithTax($importe);
        $nowCanary = new \DateTimeImmutable('now', new \DateTimeZone('Atlantic/Canary'));

        $holdedId = $this->holdedPort->createInvoice(
            $contactId,
            new ExpedienteInvoiceData(
                description: $concepto,
                totalWithTax: $breakdown['total'],
                itemName: $concepto,
                subtotal: $breakdown['subtotal'],
                taxKey: $breakdown['taxKey'],
                dateUnix: $nowCanary->getTimestamp(),
                taxes: $breakdown['taxes'],
            ),
        );

        $pdfContent = $this->holdedPort->getInvoicePdf($holdedId);
        $numero = 'FAC-' . $nowCanary->format('Ymd-His');
        $pdfPath = $this->savePdf($contactId, $numero, $pdfContent);

        $invoice = new Invoice(
            id: Uuid::v4()->toRfc4122(),
            expedienteId: $expedienteId,
            holdedId: $holdedId,
            numero: $numero,
            concepto: $concepto,
            modalidad: $modalidad,
            fecha: $nowCanary,
            importe: $breakdown['total'],
            estadoHolded: 'draft',
            pdfPath: $pdfPath,
            createdAt: new \DateTimeImmutable(),
        );

        $this->invoiceRepository->save($invoice);

        if ($phone !== '') {
            $this->logger->info(
                \sprintf('[TWILIO-SIM] WhatsApp→%s: Factura %s (%.2f€) generada.', $phone, $numero, $breakdown['total']),
            );
        }

        return $invoice;
    }

    private function savePdf(string $contactId, string $numero, string $content): string
    {
        $relDir = 'storage/invoices/client_' . $contactId;
        $absDir = $this->projectDir . '/public/' . $relDir;

        if (!is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }

        $filename = $numero . '.pdf';
        file_put_contents($absDir . '/' . $filename, $content);

        return $relDir . '/' . $filename;
    }
}
