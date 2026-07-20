<?php

declare(strict_types=1);

namespace App\Application\Port;

interface StripePort
{
    /**
     * Crea una sesión de Checkout en Stripe para un pago único.
     * Retorna la URL de la sesión y el session_id.
     *
     * @return array{url: string, sessionId: string}
     */
    /**
     * @param array<string, string> $metadata
     *
     * @return array{url: string, sessionId: string}
     */
    public function createCheckoutSession(
        string $amountCents,
        string $expedienteId,
        string $successUrl,
        string $cancelUrl,
        array $metadata = [],
    ): array;

    /**
     * Crea una suscripción de 3 cuotas (domiciliación SEPA o tarjeta).
     * Retorna la URL para completar el proceso en Stripe.
     */
    public function createSubscription3Months(string $amountCents, string $expedienteId, string $successUrl, string $cancelUrl): array;

    /**
     * @return array{paymentStatus: string, metadata: array<string, string>}
     */
    public function retrieveCheckoutSession(string $sessionId): array;
}
