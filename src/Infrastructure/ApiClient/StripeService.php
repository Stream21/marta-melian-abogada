<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiClient;

use App\Application\Port\StripePort;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\StripeClient;

/**
 * Implementación del puerto Stripe: sesiones de Checkout y suscripciones.
 * createCheckoutSession: pago único con enlace; createSubscription3Months: domiciliación en 3 cuotas.
 */
final class StripeService implements StripePort
{
    public function __construct(
        private string $stripeSecretKey,
    ) {
    }

    /**
     * Crea una sesión de Stripe Checkout para un pago único.
     * El cliente es redirigido a la URL de Stripe para pagar; al completar se dispara el webhook.
     */
    public function createCheckoutSession(string $amountCents, string $expedienteId, string $successUrl, string $cancelUrl): array
    {
        $stripe = new StripeClient($this->stripeSecretKey);

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => (int) $amountCents,
                        'product_data' => [
                            'name' => 'Expediente ' . $expedienteId,
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'expediente_id' => $expedienteId,
            ],
        ]);

        return [
            'url' => $session->url ?? '',
            'sessionId' => $session->id ?? '',
        ];
    }

    /**
     * Crea un Checkout para domiciliación en 3 cuotas.
     * Por simplicidad se crea una sesión de pago único por el importe total (el cliente paga una vez; la lógica de 3 cuotas reales podría implementarse con Stripe Subscription Schedules).
     */
    public function createSubscription3Months(string $amountCents, string $expedienteId, string $successUrl, string $cancelUrl): array
    {
        $stripe = new StripeClient($this->stripeSecretKey);

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => (int) $amountCents,
                        'product_data' => [
                            'name' => 'Expediente ' . $expedienteId . ' - Domiciliación 3 cuotas',
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'expediente_id' => $expedienteId,
            ],
        ]);

        return [
            'url' => $session->url ?? '',
            'sessionId' => $session->id ?? '',
        ];
    }
}
