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
    public function createCheckoutSession(
        string $amountCents,
        string $expedienteId,
        string $successUrl,
        string $cancelUrl,
        array $metadata = [],
    ): array {
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
            'metadata' => array_merge(['expediente_id' => $expedienteId], $metadata),
        ]);

        return [
            'url' => $session->url ?? '',
            'sessionId' => $session->id ?? '',
        ];
    }

    /**
     * @return array{paymentStatus: string, metadata: array<string, string>}
     */
    public function retrieveCheckoutSession(string $sessionId): array
    {
        $stripe = new StripeClient($this->stripeSecretKey);
        $session = $stripe->checkout->sessions->retrieve($sessionId);

        $metadata = [];
        if (null !== $session->metadata) {
            foreach ($session->metadata as $key => $value) {
                $metadata[(string) $key] = (string) $value;
            }
        }

        return [
            'paymentStatus' => (string) ($session->payment_status ?? ''),
            'metadata' => $metadata,
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
