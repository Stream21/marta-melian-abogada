<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ManualPaymentRequest;
use App\Application\DTO\PaymentResult;
use App\Application\Port\ContratacionRealtimePort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Port\HoldedPort;
use App\Application\Service\CalendarioCobrosService;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentHoldedEstado;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\ContratacionRepositoryInterface;
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
        private ContratacionRepositoryInterface $contratacionRepository,
        private CalendarioCobrosService $calendarioCobrosService,
        private ContratacionRealtimePort $realtime,
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
            $invoiceData = [
                'client' => $request->clientName ?: $expediente->clientName(),
                'reference' => $request->caseReference ?: $expediente->caseReference(),
                'amount' => $request->amount,
            ];
            $holdedInvoiceId = $this->holdedPort->createInvoice($invoiceData);
            if ('' === $holdedInvoiceId) {
                throw new \RuntimeException('Holded no devolvió un identificador de factura.');
            }
            $this->holdedPort->markAsPaid($holdedInvoiceId);

            $pdfContent = $this->holdedPort->getInvoicePdf($holdedInvoiceId);
            if (!str_starts_with($pdfContent, '%PDF-')) {
                throw new \RuntimeException('Holded devolvió un archivo que no es un PDF válido.');
            }
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
                PaymentHoldedEstado::Sincronizado,
                null,
                $now,
                $request->cuotaNumero,
            );

            $this->paymentRepository->save($payment);

            $calendario = $expediente->calendarioPagos();
            if (null !== $request->cuotaNumero) {
                $calendario = $this->calendarioCobrosService->marcarCuotaPagada($calendario, $request->cuotaNumero);
            }

            $expedienteActualizado = $expediente->withPaymentStatus('paid');
            if (null !== $calendario) {
                $expedienteActualizado = $expedienteActualizado->withCalendarioPagos($calendario);
            }
            $this->expedienteRepository->save($expedienteActualizado);

            $this->contratacionRepository->saveHito(new \App\Domain\Entity\ExpedienteHito(
                bin2hex(random_bytes(16)),
                $expedienteId,
                'pago_manual_registrado',
                sprintf(
                    'Cobro manual registrado%s.',
                    null !== $request->cuotaNumero ? ' (cuota ' . $request->cuotaNumero . ')' : '',
                ),
                \App\Domain\Entity\ActorHitoExpediente::Abogado,
                $now,
            ));

            $this->realtime->publishContratacionUpdate($request->expedienteId, [
                'type' => 'pago_recibido',
                'paymentId' => $payment->id()->value(),
                'cuotaNumero' => $request->cuotaNumero,
                'amount' => $request->amount,
                'actor' => 'abogado',
                'expedienteNumero' => $expediente->numero(),
                'clienteNombre' => $expediente->clientName(),
            ]);

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
