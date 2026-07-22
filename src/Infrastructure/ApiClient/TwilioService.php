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

        try {
            $client->messages->create(
                'whatsapp:' . $to,
                [
                    'from' => 'whatsapp:' . $this->resolveWhatsAppFrom(),
                    'body' => $message,
                ]
            );
        } catch (\Twilio\Exceptions\RestException $e) {
            throw new \RuntimeException($this->humanizeTwilioError('WhatsApp', $to, $e), 0, $e);
        } catch (\Twilio\Exceptions\TwilioException $e) {
            throw new \RuntimeException(
                'No se pudo enviar el mensaje de WhatsApp. Inténtelo de nuevo más tarde.',
                0,
                $e,
            );
        }
    }

    public function sendSmsMessage(string $to, string $message): void
    {
        if (!$this->isSmsConfigured()) {
            throw new \RuntimeException(
                'Twilio SMS no configurado. Revise en .env: '
                . implode(', ', $this->smsConfigFaltante())
                . '. Tras editar .env: docker compose up -d php && docker compose exec php php bin/console cache:clear',
            );
        }

        $to = $this->resolveToNumber($to);
        $client = new Client($this->resolveAccountSid(), $this->resolveAuthToken());

        try {
            $client->messages->create(
                $to,
                [
                    'from' => $this->resolveSmsFrom(),
                    'body' => $message,
                ]
            );
        } catch (\Twilio\Exceptions\RestException $e) {
            throw new \RuntimeException($this->humanizeTwilioError('SMS', $to, $e), 0, $e);
        } catch (\Twilio\Exceptions\TwilioException $e) {
            throw new \RuntimeException(
                'No se pudo enviar el SMS de verificación. Inténtelo de nuevo o contacte con el despacho.',
                0,
                $e,
            );
        }
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

    private function humanizeTwilioError(string $canal, string $to, \Twilio\Exceptions\RestException $e): string
    {
        $detail = trim($e->getMessage());
        $lower = strtolower($detail);

        if (
            str_contains($lower, 'unverified')
            || str_contains($lower, 'trial')
            || 21608 === $e->getCode()
            || 21265 === $e->getCode()
        ) {
            return sprintf(
                'Twilio (cuenta de prueba) no puede enviar %s a %s. Verifique el número en la consola de Twilio o use una cuenta con permisos SMS internacionales.',
                $canal,
                $to,
            );
        }

        if (
            str_contains($lower, 'permission')
            || str_contains($lower, 'not enabled')
            || str_contains($lower, 'geo-permissions')
            || 21408 === $e->getCode()
        ) {
            return sprintf(
                'Twilio no tiene permisos geográficos para enviar %s a %s. Active el país destino en Messaging → Geo Permissions.',
                $canal,
                $to,
            );
        }

        if ('' !== $detail) {
            return sprintf('Error Twilio al enviar %s: %s', $canal, $detail);
        }

        return sprintf('No se pudo enviar el %s de verificación a %s.', $canal, $to);
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
