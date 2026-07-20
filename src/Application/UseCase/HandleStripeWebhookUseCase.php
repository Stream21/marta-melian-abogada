<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\FinalizarPagoStripeService;
use App\Application\Service\PaymentHoldedSyncService;
use App\Domain\Entity\PaymentHoldedEstado;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use Psr\Log\LoggerInterface;

final class HandleStripeWebhookUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private PaymentHoldedSyncService $holdedSync,
        private FinalizarPagoStripeService $finalizarPagoStripe,
        private LoggerInterface $logger,
        private string $stripeWebhookSecret,
    ) {
    }

    /**
     * Verifica la firma del payload y procesa el evento checkout.session.completed.
     * Retorna true si se procesó correctamente, false si la firma es inválida.
     */
    public function __invoke(string $payload, string $signature): bool
    {
        if ($this->stripeWebhookSecret === '' || !$this->verifySignature($payload, $signature)) {
            return false;
        }

        $data = json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);
        $type = $data['type'] ?? null;

        if ($type !== 'checkout.session.completed') {
            return true;
        }

        $session = $data['data']['object'] ?? [];
        $sessionId = $session['id'] ?? null;
        if (!$sessionId) {
            return true;
        }

        $payment = $this->paymentRepository->findByStripeSessionId($sessionId);
        if ($payment === null) {
            return true;
        }

        $cuotaNumero = $this->finalizarPagoStripe->resolverCuotaNumero($session, $payment);

        if ($payment->status() === PaymentStatus::Paid) {
            $expediente = $this->expedienteRepository->findById($payment->expedienteId());
            if (null !== $expediente) {
                $this->finalizarPagoStripe->aplicar($payment, $cuotaNumero, false, false);
                if ($payment->holdedEstado() === PaymentHoldedEstado::PendienteSync
                    || $payment->holdedEstado() === PaymentHoldedEstado::Error
                ) {
                    $result = $this->holdedSync->sync($payment, $expediente);
                    $this->paymentRepository->save($result['payment']);
                }
            }

            return true;
        }

        $this->finalizarPagoStripe->aplicar($payment, $cuotaNumero);

        return true;
    }

    private function verifySignature(string $payload, string $signature): bool
    {
        $elements = explode(',', $signature);
        $timestamp = null;
        $v1 = null;
        foreach ($elements as $element) {
            if (str_starts_with($element, 't=')) {
                $timestamp = substr($element, 2);
            }
            if (str_starts_with($element, 'v1=')) {
                $v1 = substr($element, 3);
            }
        }
        if ($timestamp === null || $v1 === null) {
            return false;
        }
        $signed = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signed, $this->stripeWebhookSecret);

        return hash_equals($expected, $v1);
    }
}
