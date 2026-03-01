<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ManualPaymentRequest;
use App\Application\DTO\PaymentResult;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Port\HoldedPort;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\PaymentId;
use Psr\Log\LoggerInterface;

final class CreateManualPaymentUseCase
{
    public function __construct(
        private HoldedPort $holdedPort,
        private ExpedienteFileStoragePort $fileStorage,
        private PaymentRepositoryInterface $paymentRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ManualPaymentRequest $request): PaymentResult
    {
        $expedienteId = new ExpedienteId($request->expedienteId);
        $expediente = $this->expedienteRepository->findById($expedienteId);

        if ($expediente === null) {
            return PaymentResult::failure('expediente_not_found', 'Expediente no encontrado.');
        }

        try {
            // Crear factura en Holded
            $invoiceData = [
                'client' => $request->clientName ?: $expediente->clientName(),
                'reference' => $request->caseReference ?: $expediente->caseReference(),
                'amount' => $request->amount,
            ];
            $holdedInvoiceId = $this->holdedPort->createInvoice($invoiceData);

            // Marcar como cobrada en Holded
            $this->holdedPort->markAsPaid($holdedInvoiceId);

            // Descargar PDF y guardar en carpeta del expediente
            $pdfContent = $this->holdedPort->getInvoicePdf($holdedInvoiceId);
            $filename = 'factura_' . $holdedInvoiceId . '.pdf';
            $pdfPath = $this->fileStorage->savePdf($expedienteId, $filename, $pdfContent);

            $now = new \DateTimeImmutable('now');
            $payment = new Payment(
                PaymentId::generate(),
                $expedienteId,
                PaymentStatus::Paid,
                PaymentType::Manual,
                $holdedInvoiceId,
                null,
                $request->amount,
                $pdfPath,
                $now,
                $now,
            );

            $this->paymentRepository->save($payment);

            // Actualizar estado de pago del expediente (persistir expediente con payment_status actualizado)
            $expedienteUpdated = new \App\Domain\Entity\Expediente(
                $expediente->id(),
                $expediente->numero(),
                $expediente->titulo(),
                $expediente->estado(),
                $expediente->fechaApertura(),
                $expediente->clientName(),
                $expediente->caseReference(),
                $expediente->folderPath(),
                'paid',
            );
            $this->expedienteRepository->save($expedienteUpdated);

            $pdfUrl = '/api/expedientes/' . $expedienteId->value() . '/invoices/' . $payment->id()->value() . '/pdf';

            return PaymentResult::success($payment->id()->value(), $pdfPath, $pdfUrl);
        } catch (\Throwable $e) {
            $this->logger->error('CreateManualPayment: Holded o almacenamiento fallido', [
                'expedienteId' => $request->expedienteId,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failure(
                'holded_error',
                'No se pudo crear la factura. Reintente más tarde.'
            );
        }
    }
}
