<?php

declare(strict_types=1);

namespace App\Application\Port;

interface TwilioPort
{
    /**
     * Envía un mensaje por WhatsApp al número indicado.
     * El número debe incluir código de país (ej. +34600000000).
     */
    public function sendWhatsAppMessage(string $to, string $message): void;

    /**
     * Envía un SMS al número indicado.
     * El número debe incluir código de país (ej. +34600000000).
     */
    public function sendSmsMessage(string $to, string $message): void;

    public function isWhatsAppConfigured(): bool;

    public function isSmsConfigured(): bool;
}
