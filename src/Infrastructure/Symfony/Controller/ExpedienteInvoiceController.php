<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\HoldedPort;
use App\Domain\Entity\PaymentHoldedEstado;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\PaymentId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/expedientes', name: 'api_expedientes_invoices_')]
final class ExpedienteInvoiceController extends AbstractController
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private HoldedPort $holdedPort,
        private string $projectDir = '',
    ) {
    }

    /**
     * PDF de la factura única del expediente (Holded).
     */
    #[Route(path: '/{expedienteId}/invoice/pdf', name: 'expediente_invoice_pdf', methods: ['GET'])]
    public function expedienteInvoicePdf(string $expedienteId): Response
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $holdedInvoiceId = trim((string) ($expediente->holdedInvoiceId() ?? ''));
        if ('' === $holdedInvoiceId) {
            $payments = $this->paymentRepository->findByExpediente(new ExpedienteId($expedienteId));
            foreach ($payments as $payment) {
                if (PaymentHoldedEstado::Sincronizado === $payment->holdedEstado()
                    && null !== $payment->pdfPath()
                    && is_file($this->projectDir . '/' . $payment->pdfPath())
                ) {
                    return new BinaryFileResponse($this->projectDir . '/' . $payment->pdfPath(), 200, [
                        'Content-Type' => 'application/pdf',
                    ]);
                }
                $candidate = trim((string) ($payment->holdedInvoiceId() ?? ''));
                if ('' !== $candidate) {
                    $holdedInvoiceId = $candidate;
                    break;
                }
            }
        }

        if ('' === $holdedInvoiceId) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        try {
            $pdf = $this->holdedPort->getInvoicePdf($holdedInvoiceId);
        } catch (\Throwable) {
            return new Response('', Response::HTTP_BAD_GATEWAY);
        }

        if (!str_starts_with($pdf, '%PDF-')) {
            return new Response('', Response::HTTP_BAD_GATEWAY);
        }

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="factura-' . $expediente->numero() . '.pdf"',
        ]);
    }

    #[Route(path: '/{expedienteId}/invoices/{paymentId}/pdf', name: 'pdf', methods: ['GET'])]
    public function pdf(string $expedienteId, string $paymentId): Response
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        $payment = $this->paymentRepository->findById(new PaymentId($paymentId));

        if ($expediente === null || $payment === null) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        if ($payment->expedienteId()->value() !== $expedienteId) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        if (null !== $payment->pdfPath() && PaymentHoldedEstado::Sincronizado === $payment->holdedEstado()) {
            $fullPath = $this->projectDir . '/' . $payment->pdfPath();
            if (is_file($fullPath)) {
                $header = (string) file_get_contents($fullPath, false, null, 0, 5);
                if (str_starts_with($header, '%PDF-')) {
                    return new BinaryFileResponse($fullPath, 200, [
                        'Content-Type' => 'application/pdf',
                    ]);
                }
            }
        }

        return $this->expedienteInvoicePdf($expedienteId);
    }
}
