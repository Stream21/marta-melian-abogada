<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\EmailPort;
use App\Application\Port\TwilioPort;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;

/**
 * Notificaciones al cliente (alta, enlace de acceso): WhatsApp o email.
 * El SMS queda reservado al OTP de firma (FirmaOtpService).
 */
final class NotificarAltaExpedienteService
{
    public function __construct(
        private TwilioPort $twilioPort,
        private EmailPort $emailPort,
        private string $frontendBaseUrl,
    ) {
    }

    /**
     * @param string[] $canalesSolicitados
     *
     * @return string[]
     */
    public function notificar(
        Expediente $expediente,
        Cliente $cliente,
        string $tramiteNombre,
        array $canalesSolicitados,
    ): array {
        $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        $mensaje = sprintf(
            'Bienvenido/a a Marta Melián Abogados. Su expediente %s (%s) ha sido abierto. Acceda aquí para iniciar la contratación: %s',
            $expediente->numero(),
            $tramiteNombre,
            $accessUrl,
        );

        return $this->enviarPorCanales($cliente, $expediente->numero(), $mensaje, $canalesSolicitados);
    }

    /**
     * @param string[] $canalesSolicitados
     *
     * @return string[]
     */
    public function enviarEnlace(
        Expediente $expediente,
        Cliente $cliente,
        string $tramiteNombre,
        array $canalesSolicitados,
    ): array {
        $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        $mensaje = sprintf(
            'Acceda a su expediente %s (%s) en Marta Melián Abogados: %s',
            $expediente->numero(),
            $tramiteNombre,
            $accessUrl,
        );

        return $this->enviarPorCanales($cliente, $expediente->numero(), $mensaje, $canalesSolicitados);
    }

    /**
     * @param string[] $canalesSolicitados
     *
     * @return string[]
     */
    private function enviarPorCanales(Cliente $cliente, string $numeroExpediente, string $mensaje, array $canalesSolicitados): array
    {
        $canales = [];
        $telefono = trim($cliente->telefono());
        $email = trim($cliente->email());

        if (in_array('whatsapp', $canalesSolicitados, true) && '' !== $telefono) {
            try {
                $this->twilioPort->sendWhatsAppMessage($telefono, $mensaje);
                $canales[] = 'whatsapp';
            } catch (\Throwable) {
                $canales[] = 'whatsapp_error';
            }
        }

        if (in_array('email', $canalesSolicitados, true) && '' !== $email) {
            $this->emailPort->send(
                $email,
                'Expediente ' . $numeroExpediente,
                $mensaje,
            );
            $canales[] = 'email';
        }

        return $canales;
    }
}
