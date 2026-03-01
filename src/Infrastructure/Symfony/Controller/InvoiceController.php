<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Service\PaymentFacadeService;
use App\Domain\Entity\Invoice;
use App\Domain\Repository\InvoiceRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api', name: 'api_invoice_')]
final class InvoiceController extends AbstractController
{
    public function __construct(
        private readonly PaymentFacadeService $facade,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** Proxy to HoldedApiClient::listContacts(). */
    #[Route(path: '/contacts', name: 'contacts_list', methods: ['GET'])]
    public function listContacts(): JsonResponse
    {
        $contacts = $this->facade->listContacts();

        return new JsonResponse($contacts);
    }

    /** Returns invoices for a given expediente from the PostgreSQL invoice table. */
    #[Route(path: '/expedientes/{id}/invoices', name: 'expediente_invoices', methods: ['GET'])]
    public function expedienteInvoices(string $id): JsonResponse
    {
        $invoices = $this->invoiceRepository->findByExpedienteId($id);

        return new JsonResponse(array_map($this->serializeInvoice(...), $invoices));
    }

    /**
     * Creates a Holded invoice linked to an expediente.
     * Body: { expedienteId, concepto, modalidad, importe, phone?, email? }
     */
    #[Route(path: '/invoices/holded', name: 'create_holded', methods: ['POST'])]
    public function createHolded(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $expedienteId = (string) ($data['expedienteId'] ?? '');
        $concepto     = (string) ($data['concepto'] ?? '');
        $modalidad    = (string) ($data['modalidad'] ?? 'honorarios');
        $importe      = (float) ($data['importe'] ?? 0.0);
        $phone        = (string) ($data['phone'] ?? '');
        $email        = (string) ($data['email'] ?? '');

        if ($expedienteId === '' || $concepto === '' || $importe <= 0) {
            return new JsonResponse(
                ['success' => false, 'error' => 'expedienteId, concepto and importe are required'],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $invoice = $this->facade->createFromExpediente(
                expedienteId: $expedienteId,
                concepto: $concepto,
                modalidad: $modalidad,
                importe: $importe,
                phone: $phone,
                email: $email,
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['success' => false, 'error' => $e->getMessage()],
                JsonResponse::HTTP_NOT_FOUND,
            );
        } catch (\Throwable $e) {
            $this->logger->error('InvoiceController::createHolded failed', ['exception' => $e]);

            return new JsonResponse(
                ['success' => false, 'error' => 'Internal error creating invoice'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return new JsonResponse(
            ['success' => true, 'invoice' => $this->serializeInvoice($invoice)],
            JsonResponse::HTTP_CREATED,
        );
    }

    /**
     * Creates a quick invoice for an already-existing Holded contact (from /clientes page).
     * Body: { contactId, concepto, importe, phone? }
     */
    #[Route(path: '/invoices/quick', name: 'create_quick', methods: ['POST'])]
    public function createQuick(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $contactId = (string) ($data['contactId'] ?? '');
        $concepto  = (string) ($data['concepto'] ?? '');
        $importe   = (float) ($data['importe'] ?? 0.0);
        $phone     = (string) ($data['phone'] ?? '');

        if ($contactId === '' || $concepto === '' || $importe <= 0) {
            return new JsonResponse(
                ['success' => false, 'error' => 'contactId, concepto and importe are required'],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $invoice = $this->facade->createFromContact(
                contactId: $contactId,
                concepto: $concepto,
                importe: $importe,
                phone: $phone,
            );
        } catch (\Throwable $e) {
            $this->logger->error('InvoiceController::createQuick failed', ['exception' => $e]);

            return new JsonResponse(
                ['success' => false, 'error' => 'Internal error creating invoice'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return new JsonResponse(
            ['success' => true, 'invoice' => $this->serializeInvoice($invoice)],
            JsonResponse::HTTP_CREATED,
        );
    }

    /**
     * Simulates a WhatsApp notification via Twilio and returns a log entry.
     * Body: { phone? }
     */
    #[Route(path: '/invoices/{invoiceId}/whatsapp', name: 'whatsapp', methods: ['POST'])]
    public function whatsapp(string $invoiceId, Request $request): JsonResponse
    {
        $invoice = $this->invoiceRepository->findById($invoiceId);

        if ($invoice === null) {
            return new JsonResponse(
                ['success' => false, 'error' => 'Invoice not found'],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        $data  = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $phone = (string) ($data['phone'] ?? '');

        $logMessage = \sprintf(
            '[TWILIO-SIM] WhatsApp→%s: Factura %s (%.2f€) generada.',
            $phone ?: 'unknown',
            $invoice->numero(),
            $invoice->importe(),
        );

        $this->logger->info($logMessage);

        return new JsonResponse(['success' => true, 'log' => $logMessage]);
    }

    /** @return array<string, mixed> */
    private function serializeInvoice(Invoice $invoice): array
    {
        return [
            'id'           => $invoice->id(),
            'expedienteId' => $invoice->expedienteId(),
            'holdedId'     => $invoice->holdedId(),
            'numero'       => $invoice->numero(),
            'concepto'     => $invoice->concepto(),
            'modalidad'    => $invoice->modalidad(),
            'fecha'        => $invoice->fecha()->format(\DateTimeInterface::ATOM),
            'importe'      => $invoice->importe(),
            'estadoHolded' => $invoice->estadoHolded(),
            'pdfPath'      => $invoice->pdfPath(),
            'pdfUrl'       => $invoice->pdfPath() !== null
                ? '/' . $invoice->pdfPath()
                : null,
            'createdAt'    => $invoice->createdAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
