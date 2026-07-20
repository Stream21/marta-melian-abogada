<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

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
        private string $projectDir = '',
    ) {
    }

    #[Route(path: '/{expedienteId}/invoices/{paymentId}/pdf', name: 'pdf', methods: ['GET'])]
    public function pdf(string $expedienteId, string $paymentId): Response
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        $payment = $this->paymentRepository->findById(new PaymentId($paymentId));

        if ($expediente === null || $payment === null || null === $payment->invoicePdfUrl()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        if ($payment->holdedEstado() !== PaymentHoldedEstado::Sincronizado) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        if ($payment->expedienteId()->value() !== $expedienteId) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $fullPath = $this->projectDir . '/' . $payment->pdfPath();
        if (!is_file($fullPath)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $header = (string) file_get_contents($fullPath, false, null, 0, 5);
        if (!str_starts_with($header, '%PDF-')) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse($fullPath, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
