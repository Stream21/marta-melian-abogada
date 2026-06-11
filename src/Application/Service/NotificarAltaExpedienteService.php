<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\EmailPort;
use App\Application\Port\TwilioPort;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;

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

        $canales = [];

        $telefono = trim($cliente->telefono());
        if (in_array('whatsapp', $canalesSolicitados, true) && '' !== $telefono) {
            try {
                $this->twilioPort->sendWhatsAppMessage($telefono, $mensaje);
                $canales[] = 'whatsapp';
            } catch (\Throwable) {
                $canales[] = 'whatsapp_error';
            }
        }

        $email = trim($cliente->email());
        if (in_array('email', $canalesSolicitados, true) && '' !== $email) {
            $this->emailPort->send(
                $email,
                'Apertura de expediente ' . $expediente->numero(),
                $mensaje,
            );
            $canales[] = 'email';
        }

        return $canales;
    }
}
