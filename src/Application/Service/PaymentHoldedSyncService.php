<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Port\HoldedPort;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentHoldedEstado;
use Psr\Log\LoggerInterface;

final class PaymentHoldedSyncService
{
    public function __construct(
        private HoldedPort $holdedPort,
        private ExpedienteFileStoragePort $fileStorage,
        private NotificarFalloSyncHoldedService $notificarFallo,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{payment: Payment, success: bool, error?: string}
     */
    public function sync(Payment $payment, Expediente $expediente, bool $isRetry = false): array
    {
        if ($payment->status()->value !== 'paid') {
            return [
                'payment' => $payment,
                'success' => false,
                'error' => 'El cobro debe estar marcado como pagado antes de sincronizar con Holded.',
            ];
        }

        if ($payment->holdedEstado() === PaymentHoldedEstado::Sincronizado) {
            return ['payment' => $payment, 'success' => true];
        }

        $holdedInvoiceId = trim((string) ($payment->holdedInvoiceId() ?? ''));

        try {
            if ('' === $holdedInvoiceId) {
                $holdedInvoiceId = $this->holdedPort->createInvoice([
                    'client' => $expediente->clientName(),
                    'reference' => $expediente->caseReference(),
                    'amount' => $payment->amount(),
                ]);

                if ('' === $holdedInvoiceId) {
                    throw new \RuntimeException('Holded no devolvió un identificador de factura.');
                }
            }

            $this->holdedPort->markAsPaid($holdedInvoiceId);

            $pdfPath = $payment->pdfPath();
            try {
                $pdfContent = $this->holdedPort->getInvoicePdf($holdedInvoiceId);
                if (!$this->isValidPdf($pdfContent)) {
                    throw new \RuntimeException('Holded devolvió un archivo que no es un PDF válido.');
                }
                $filename = 'factura_' . $holdedInvoiceId . '.pdf';
                $pdfPath = $this->fileStorage->savePdf($payment->expedienteId(), $filename, $pdfContent);
            } catch (\Throwable $e) {
                $this->logger->warning('PaymentHoldedSync: PDF no descargado', [
                    'paymentId' => $payment->id()->value(),
                    'holdedInvoiceId' => $holdedInvoiceId,
                    'error' => $e->getMessage(),
                ]);
            }

            $now = new \DateTimeImmutable('now');
            $updated = $payment->withHoldedSync(
                PaymentHoldedEstado::Sincronizado,
                $holdedInvoiceId,
                $pdfPath,
                null,
                $now,
            );

            return ['payment' => $updated, 'success' => true];
        } catch (\Throwable $e) {
            $this->logger->error('PaymentHoldedSync: fallo Holded', [
                'paymentId' => $payment->id()->value(),
                'expedienteId' => $payment->expedienteId()->value(),
                'error' => $e->getMessage(),
            ]);

            $estadoOnFailure = $isRetry
                ? PaymentHoldedEstado::Error
                : PaymentHoldedEstado::PendienteSync;

            $updated = $payment->withHoldedSync(
                $estadoOnFailure,
                '' !== $holdedInvoiceId ? $holdedInvoiceId : null,
                $payment->pdfPath(),
                $e->getMessage(),
            );

            if (!$isRetry) {
                $this->notificarFallo->notificar($expediente, $updated, $e->getMessage());
            }

            return [
                'payment' => $updated,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function markPendingSync(Payment $payment): Payment
    {
        return $payment->withHoldedSync(PaymentHoldedEstado::PendienteSync, null, null, null);
    }

    private function isValidPdf(string $content): bool
    {
        return str_starts_with($content, '%PDF-');
    }
}
