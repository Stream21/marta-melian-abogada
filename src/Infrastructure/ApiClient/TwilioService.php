<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiClient;

use App\Application\Port\TwilioPort;
use App\Application\Service\TelefonoNormalizer;
use Twilio\Rest\Client;

/**
 * Envío de mensajes por SMS y WhatsApp vía Twilio.
 */
final class TwilioService implements TwilioPort
{
    public function __construct(
        private string $accountSid,
        private string $authToken,
        private string $whatsAppFrom,
        private string $smsFrom,
        private TelefonoNormalizer $telefonoNormalizer,
    ) {
    }

    public function sendWhatsAppMessage(string $to, string $message): void
    {
        $to = $this->resolveToNumber($to);
        $client = new Client($this->resolveAccountSid(), $this->resolveAuthToken());

        $client->messages->create(
            'whatsapp:' . $to,
            [
                'from' => 'whatsapp:' . $this->resolveWhatsAppFrom(),
                'body' => $message,
            ]
        );
    }

    public function sendSmsMessage(string $to, string $message): void
    {
        if (!$this->isSmsConfigured()) {
            throw new \RuntimeException(
                'Twilio SMS no configurado. Revise en .env: '
                . implode(', ', $this->smsConfigFaltante())
                . '. Tras editar .env: docker-compose up -d php && docker-compose exec php php bin/console cache:clear',
            );
        }

        $to = $this->resolveToNumber($to);
        $client = new Client($this->resolveAccountSid(), $this->resolveAuthToken());

        $client->messages->create(
            $to,
            [
                'from' => $this->resolveSmsFrom(),
                'body' => $message,
            ]
        );
    }

    private function resolveToNumber(string $to): string
    {
        $normalized = $this->telefonoNormalizer->normalize($to);
        if (null === $normalized || !$this->telefonoNormalizer->isValid($normalized)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Número de teléfono no válido: %s. Use formato internacional E.164, por ejemplo +346XXXXXXXX.',
                    $to,
                ),
            );
        }

        return $normalized;
    }

    public function isWhatsAppConfigured(): bool
    {
        return '' !== $this->resolveAccountSid()
            && '' !== $this->resolveAuthToken()
            && '' !== $this->resolveWhatsAppFrom();
    }

    public function isSmsConfigured(): bool
    {
        return [] === $this->smsConfigFaltante();
    }

    /**
     * @return list<string>
     */
    private function smsConfigFaltante(): array
    {
        $faltante = [];
        if ('' === $this->resolveAccountSid()) {
            $faltante[] = 'TWILIO_ACCOUNT_SID';
        }
        if ('' === $this->resolveAuthToken()) {
            $faltante[] = 'TWILIO_AUTH_TOKEN';
        }
        if ('' === $this->resolveSmsFrom()) {
            $faltante[] = 'TWILIO_SMS_FROM';
        }

        return $faltante;
    }

    private function resolveAccountSid(): string
    {
        return $this->resolveEnv('TWILIO_ACCOUNT_SID', $this->accountSid);
    }

    private function resolveAuthToken(): string
    {
        return $this->resolveEnv('TWILIO_AUTH_TOKEN', $this->authToken);
    }

    private function resolveWhatsAppFrom(): string
    {
        return $this->resolveEnv('TWILIO_WHATSAPP_FROM', $this->whatsAppFrom);
    }

    private function resolveSmsFrom(): string
    {
        return $this->resolveEnv('TWILIO_SMS_FROM', $this->smsFrom);
    }

    private function resolveEnv(string $key, string $injected): string
    {
        $value = trim($injected);
        if ('' !== $value) {
            return $value;
        }

        $fromEnv = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if (false === $fromEnv || null === $fromEnv) {
            return '';
        }

        return trim((string) $fromEnv);
    }
}
