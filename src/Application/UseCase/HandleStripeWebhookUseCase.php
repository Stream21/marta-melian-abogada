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
        if ($this->stripeWebhookSecret === '') {
            $this->logger->error('Webhook Stripe rechazado: STRIPE_WEBHOOK_SECRET vacío');

            return false;
        }

        if (!$this->verifySignature($payload, $signature)) {
            $this->logger->warning('Webhook Stripe rechazado: firma inválida', [
                'signaturePresent' => '' !== trim($signature),
            ]);

            return false;
        }

        try {
            $data = json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->error('Webhook Stripe: payload JSON inválido', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $type = $data['type'] ?? null;

        if ($type !== 'checkout.session.completed') {
            $this->logger->info('Webhook Stripe ignorado (evento no gestionado)', [
                'type' => $type,
            ]);

            return true;
        }

        $session = $data['data']['object'] ?? [];
        $sessionId = $session['id'] ?? null;
        if (!$sessionId) {
            $this->logger->warning('Webhook Stripe checkout.session.completed sin session id');

            return true;
        }

        $payment = $this->paymentRepository->findByStripeSessionId($sessionId);
        if ($payment === null) {
            $this->logger->warning('Webhook Stripe: no hay Payment local para la sesión', [
                'sessionId' => $sessionId,
            ]);

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

            $this->logger->info('Webhook Stripe: sesión ya cobrada (idempotente)', [
                'sessionId' => $sessionId,
                'paymentId' => $payment->id()->value(),
            ]);

            return true;
        }

        $this->finalizarPagoStripe->aplicar($payment, $cuotaNumero);

        $this->logger->info('Webhook Stripe: pago finalizado', [
            'sessionId' => $sessionId,
            'paymentId' => $payment->id()->value(),
            'cuotaNumero' => $cuotaNumero,
        ]);

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
