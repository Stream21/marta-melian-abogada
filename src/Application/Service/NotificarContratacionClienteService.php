<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\PasoContratacionCliente;

final class NotificarContratacionClienteService
{
    public function __construct(
        private DespacharNotificacionClienteService $despachar,
        private string $frontendBaseUrl,
    ) {
    }

    public function notificarAccionRequerida(
        Expediente $expediente,
        Cliente $cliente,
        PasoContratacionCliente $paso,
        string $nota,
    ): void {
        $accessUrl = $this->accessUrl($expediente);
        $mensaje = sprintf(
            "Su abogado le ha enviado un mensaje sobre el paso «%s» de su expediente %s:\n\n%s\n\nAcceda a su portal para continuar:\n%s",
            $paso->label(),
            $expediente->numero(),
            $nota !== '' ? $nota : 'Revise el paso indicado y complete la acción pendiente en su portal.',
            $accessUrl,
        );

        $this->despachar->despachar(
            $expediente,
            'contratacion_accion',
            sprintf('Acción requerida — Expediente %s', $expediente->numero()),
            $mensaje,
            'notificacion_contratacion_accion',
            'Se ha notificado al cliente una acción requerida en contratación por %s.',
        );
    }

    /**
     * Aviso automático cuando el abogado valida un paso y desbloquea el siguiente
     * (datos → firmas, o firmas → pago).
     */
    public function notificarPasoDisponible(
        Expediente $expediente,
        Cliente $cliente,
        PasoContratacionCliente $siguientePaso,
    ): void {
        $contenido = $this->contenidoPasoDisponible($expediente, $siguientePaso);
        if (null === $contenido) {
            return;
        }

        $this->despachar->despachar(
            $expediente,
            'contratacion_paso_disponible_' . $siguientePaso->value,
            $contenido['asunto'],
            $contenido['mensaje'],
            'notificacion_contratacion_paso',
            sprintf(
                'Se ha notificado al cliente que puede continuar con «%s» por %%s.',
                $siguientePaso->label(),
            ),
        );
    }

    /**
     * @return array{asunto: string, mensaje: string}|null
     */
    private function contenidoPasoDisponible(
        Expediente $expediente,
        PasoContratacionCliente $siguientePaso,
    ): ?array {
        $accessUrl = $this->accessUrl($expediente);
        $numero = $expediente->numero();

        return match ($siguientePaso) {
            PasoContratacionCliente::Firmas => [
                'asunto' => sprintf('Documentos listos para firmar — Expediente %s', $numero),
                'mensaje' => sprintf(
                    "Su abogado ha revisado y aceptado sus datos del expediente %s.\n\n"
                    . "Ya puede acceder al portal para firmar los documentos (hoja de encargo, designación y RGPD (contrato protección de datos)):\n%s",
                    $numero,
                    $accessUrl,
                ),
            ],
            PasoContratacionCliente::Pago => [
                'asunto' => sprintf('Firmas confirmadas — Continúe con el pago — Expediente %s', $numero),
                'mensaje' => sprintf(
                    "Su abogado ha confirmado las firmas de su expediente %s.\n\n"
                    . "Ya puede acceder al portal para continuar con el pago inicial:\n%s",
                    $numero,
                    $accessUrl,
                ),
            ],
            PasoContratacionCliente::DatosCliente => null,
        };
    }

    private function accessUrl(Expediente $expediente): string
    {
        return rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
    }
}
