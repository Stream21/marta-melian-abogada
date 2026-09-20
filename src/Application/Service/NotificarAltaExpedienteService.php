<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;

/**
 * Notificaciones al cliente (alta, enlace de acceso): WhatsApp o email.
 * El SMS queda reservado al OTP de firma (FirmaOtpService).
 */
final class NotificarAltaExpedienteService
{
    public function __construct(
        private DespacharNotificacionClienteService $despachar,
        private string $frontendBaseUrl,
    ) {
    }

    /**
     * @param string[] $canalesSolicitados
     *
     * @return string[] Canales encolados
     */
    public function notificar(
        Expediente $expediente,
        Cliente $cliente,
        string $tramiteNombre,
        array $canalesSolicitados,
    ): array {
        $accessUrl = $this->accessUrl($expediente);
        $mensaje = sprintf(
            'Bienvenido/a a Marta Melián Abogados. Su expediente %s (%s) ha sido abierto. Acceda aquí para iniciar la contratación: %s',
            $expediente->numero(),
            $tramiteNombre,
            $accessUrl,
        );

        return $this->despachar->despachar(
            $expediente,
            'alta',
            'Expediente ' . $expediente->numero(),
            $mensaje,
            'notificacion_alta_expediente',
            'Se ha notificado al cliente el alta del expediente por %s.',
            Expediente::normalizarCanales($canalesSolicitados),
        );
    }

    /**
     * @param string[] $canalesSolicitados
     *
     * @return string[] Canales encolados
     */
    public function enviarEnlace(
        Expediente $expediente,
        Cliente $cliente,
        string $tramiteNombre,
        array $canalesSolicitados,
    ): array {
        $accessUrl = $this->accessUrl($expediente);
        $mensaje = sprintf(
            'Acceda a su expediente %s (%s) en Marta Melián Abogados: %s',
            $expediente->numero(),
            $tramiteNombre,
            $accessUrl,
        );

        return $this->despachar->despachar(
            $expediente,
            'enlace',
            'Expediente ' . $expediente->numero(),
            $mensaje,
            'notificacion_enlace_enviado',
            'Se ha enviado el enlace de acceso al cliente por %s.',
            Expediente::normalizarCanales($canalesSolicitados),
        );
    }

    private function accessUrl(Expediente $expediente): string
    {
        return rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
    }
}
