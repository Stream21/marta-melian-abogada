<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiClient;

use App\Application\Port\TwilioPort;
use Twilio\Rest\Client;

/**
 * Implementación del puerto Twilio: envío de mensajes por WhatsApp.
 * sendWhatsAppMessage: usa la API de Twilio para enviar al número indicado desde el número configurado (TWILIO_WHATSAPP_FROM).
 */
final class TwilioService implements TwilioPort
{
    public function __construct(
        private string $accountSid,
        private string $authToken,
        private string $whatsAppFrom,
    ) {
    }

    /**
     * Envía un mensaje por WhatsApp al número destino.
     * El número debe incluir código de país (ej. +34600000000).
     * Twilio usa el canal WhatsApp con from = whatsapp:+34... y to = whatsapp:+34...
     */
    public function sendWhatsAppMessage(string $to, string $message): void
    {
        $client = new Client($this->accountSid, $this->authToken);

        $client->messages->create(
            'whatsapp:' . $to,
            [
                'from' => 'whatsapp:' . $this->whatsAppFrom,
                'body' => $message,
            ]
        );
    }
}
