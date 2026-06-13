<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\TwilioPort;
use App\Application\Service\TelefonoNormalizer;

final class ProbarTwilioUseCase
{
    public function __construct(
        private TwilioPort $twilioPort,
        private TelefonoNormalizer $telefonoNormalizer,
    ) {
    }

    /**
     * @return array{canal: string, enviado: bool}
     */
    public function __invoke(string $canal, string $telefono, ?string $mensaje = null): array
    {
        $telefono = $this->telefonoNormalizer->normalize($telefono);
        if (null === $telefono || !$this->telefonoNormalizer->isValid($telefono)) {
            throw new \InvalidArgumentException('Teléfono no válido.');
        }

        $texto = null !== $mensaje && '' !== trim($mensaje)
            ? trim($mensaje)
            : 'Prueba de integración Twilio — Bufete Melián Abogados.';

        return match ($canal) {
            'sms' => $this->enviarSms($telefono, $texto),
            'whatsapp' => $this->enviarWhatsApp($telefono, $texto),
            default => throw new \InvalidArgumentException('Canal no válido. Use sms o whatsapp.'),
        };
    }

    /**
     * @return array{canal: string, enviado: bool}
     */
    private function enviarSms(string $telefono, string $texto): array
    {
        if (!$this->twilioPort->isSmsConfigured()) {
            throw new \InvalidArgumentException('Twilio SMS no está configurado (TWILIO_SMS_FROM).');
        }

        $this->twilioPort->sendSmsMessage($telefono, $texto);

        return ['canal' => 'sms', 'enviado' => true];
    }

    /**
     * @return array{canal: string, enviado: bool}
     */
    private function enviarWhatsApp(string $telefono, string $texto): array
    {
        if (!$this->twilioPort->isWhatsAppConfigured()) {
            throw new \InvalidArgumentException('Twilio WhatsApp no está configurado.');
        }

        $this->twilioPort->sendWhatsAppMessage($telefono, $texto);

        return ['canal' => 'whatsapp', 'enviado' => true];
    }

    /**
     * @return array{sms: bool, whatsapp: bool}
     */
    public function estado(): array
    {
        return [
            'sms' => $this->twilioPort->isSmsConfigured(),
            'whatsapp' => $this->twilioPort->isWhatsAppConfigured(),
        ];
    }
}
