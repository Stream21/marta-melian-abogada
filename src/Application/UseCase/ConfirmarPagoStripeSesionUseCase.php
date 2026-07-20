<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\StripePort;
use App\Application\Service\FinalizarPagoStripeService;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Repository\PaymentRepositoryInterface;

final class ConfirmarPagoStripeSesionUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private StripePort $stripePort,
        private FinalizarPagoStripeService $finalizarPagoStripe,
    ) {
    }

    public function __invoke(string $sessionId): void
    {
        if ('' === trim($sessionId)) {
            throw new \InvalidArgumentException('Sesión de pago no indicada.');
        }

        $payment = $this->paymentRepository->findByStripeSessionId($sessionId);
        if (null === $payment) {
            throw new \InvalidArgumentException('No se encontró el pago asociado a esta sesión.');
        }

        if (PaymentStatus::Paid === $payment->status()) {
            $this->finalizarPagoStripe->aplicar(
                $payment,
                $this->finalizarPagoStripe->resolverCuotaNumero(['metadata' => []], $payment),
                false,
                false,
            );

            return;
        }

        $session = $this->stripePort->retrieveCheckoutSession($sessionId);
        if (($session['paymentStatus'] ?? '') !== 'paid') {
            throw new \InvalidArgumentException('El pago aún no se ha completado en Stripe.');
        }

        $cuotaNumero = $this->finalizarPagoStripe->resolverCuotaNumero(
            ['metadata' => $session['metadata'] ?? []],
            $payment,
        );

        $this->finalizarPagoStripe->aplicar($payment, $cuotaNumero);
    }
}
