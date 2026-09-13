<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ManualPaymentRequest;
use App\Application\DTO\PaymentResult;
use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\CalendarioCobrosService;
use App\Application\Service\PaymentHoldedSyncService;
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
        private PaymentHoldedSyncService $holdedSync,
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

        $now = new \DateTimeImmutable('now');
        $payment = new Payment(
            PaymentId::generate(),
            $expedienteId,
            PaymentStatus::Paid,
            PaymentType::Manual,
            $expediente->holdedInvoiceId(),
            null,
            $request->amount,
            null,
            $now,
            $now,
            PaymentHoldedEstado::PendienteSync,
            null,
            null,
            $request->cuotaNumero,
        );

        $calendario = $expediente->calendarioPagos();
        if (null !== $request->cuotaNumero) {
            $calendario = $this->calendarioCobrosService->marcarCuotaPagada($calendario, $request->cuotaNumero);
        }

        $expediente = $this->holdedSync->applyPaymentStatus(
            $expediente,
            $calendario,
            (float) $request->amount,
        )->touchEstadoCambio();
        $this->paymentRepository->save($payment);
        $this->expedienteRepository->save($expediente);

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

        $result = $this->holdedSync->sync($payment, $expediente);
        $this->paymentRepository->save($result['payment']);
        if ($result['expediente']->holdedInvoiceId() !== $expediente->holdedInvoiceId()) {
            $this->expedienteRepository->save($result['expediente']);
        }

        if (!$result['success']) {
            $this->logger->warning('CreateManualPayment: cobro local OK, Holded pendiente', [
                'expedienteId' => $request->expedienteId,
                'paymentId' => $payment->id()->value(),
                'error' => $result['error'] ?? null,
            ]);
        }

        $this->realtime->publishContratacionUpdate($request->expedienteId, [
            'type' => 'pago_recibido',
            'paymentId' => $payment->id()->value(),
            'cuotaNumero' => $request->cuotaNumero,
            'amount' => $request->amount,
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);

        $synced = $result['payment'];
        $pdfUrl = $synced->invoicePdfUrl()
            ?? '/api/expedientes/' . $expedienteId->value() . '/invoice/pdf';

        return PaymentResult::success($synced->id()->value(), $synced->pdfPath(), $pdfUrl);
    }
}
