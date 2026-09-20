<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;

final class NotificarTramitacionClienteService
{
    private const INFOEXT_URL = 'https://sede.administracionespublicas.gob.es/pagina/index/directorio/infoext2';

    public function __construct(
        private DespacharNotificacionClienteService $despachar,
        private string $frontendBaseUrl,
    ) {
    }

    public function notificarPresentacionRegistrada(Expediente $expediente, Cliente $cliente): void
    {
        $this->despachar->despachar(
            $expediente,
            'presentacion',
            sprintf('Solicitud presentada — Expediente %s', $expediente->numero()),
            sprintf(
                "Su solicitud del expediente %s ha sido presentada ante la Administración.\n\n"
                . "Estado actual: pendiente de tramitación por parte de la oficina de extranjería.\n"
                . "Le avisaremos también cuando se asigne el número de expediente de extranjería "
                . "para que pueda consultar el estado en la sede electrónica y por SMS.\n\n"
                . "Consulte el detalle en su portal:\n%s",
                $expediente->numero(),
                $this->accessUrl($expediente),
            ),
            'notificacion_presentacion',
            'Se ha notificado al cliente la presentación del trámite por %s.',
        );
    }

    public function notificarSeguimientoAsignado(
        Expediente $expediente,
        Cliente $cliente,
        string $numeroExpedienteExtranjeria,
        bool $esActualizacion = false,
    ): void {
        $asunto = $esActualizacion
            ? sprintf('Seguimiento actualizado — Expediente %s', $expediente->numero())
            : sprintf('Seguimiento disponible — Expediente %s', $expediente->numero());
        $intro = $esActualizacion
            ? sprintf(
                "Se ha actualizado el número de seguimiento de su solicitud del expediente %s.\n\n"
                . "Nuevo número de expediente de extranjería: %s\n\n",
                $expediente->numero(),
                $numeroExpedienteExtranjeria,
            )
            : sprintf(
                "Ya puede consultar el estado de su solicitud del expediente %s.\n\n"
                . "Número de expediente de extranjería: %s\n\n",
                $expediente->numero(),
                $numeroExpedienteExtranjeria,
            );

        $this->despachar->despachar(
            $expediente,
            $esActualizacion ? 'seguimiento_actualizado' : 'seguimiento',
            $asunto,
            $intro
            . "Cómo consultar:\n"
            . '1) Web: '.self::INFOEXT_URL."\n"
            . sprintf("2) SMS gratuito: envíe el texto «EXPE %s» al 651 714 610\n\n", $numeroExpedienteExtranjeria)
            . "Los datos que facilite la Administración tienen carácter meramente informativo.\n\n"
            . "Portal del expediente:\n".$this->accessUrl($expediente),
            'notificacion_seguimiento',
            'Se ha notificado al cliente el número de seguimiento por %s.',
        );
    }

    public function notificarRequerimientoCliente(
        Expediente $expediente,
        Cliente $cliente,
        string $documentoNombre,
        string $nota = '',
    ): void {
        $cuerpoNota = '' !== trim($nota)
            ? sprintf("\n\nMensaje de su abogado:\n%s", trim($nota))
            : '';

        $this->despachar->despachar(
            $expediente,
            'requerimiento',
            sprintf('Acción requerida — Expediente %s', $expediente->numero()),
            sprintf(
                "Su abogado le ha solicitado completar «%s» para continuar la tramitación del expediente %s.%s\n\n"
                . "Acceda a su portal:\n%s",
                $documentoNombre,
                $expediente->numero(),
                $cuerpoNota,
                $this->accessUrl($expediente),
            ),
            'notificacion_requerimiento',
            'Se ha notificado al cliente un requerimiento de tramitación por %s.',
        );
    }

    public function notificarVueltaSeguimiento(Expediente $expediente, Cliente $cliente): void
    {
        $this->despachar->despachar(
            $expediente,
            'vuelta_seguimiento',
            sprintf('Tramitación actualizada — Expediente %s', $expediente->numero()),
            sprintf(
                "El expediente %s vuelve a estar en seguimiento ante la Administración.\n"
                . "No se requiere ninguna acción por su parte en este momento.\n\n"
                . "Portal:\n%s",
                $expediente->numero(),
                $this->accessUrl($expediente),
            ),
            'notificacion_vuelta_seguimiento',
            'Se ha notificado al cliente la vuelta a seguimiento por %s.',
        );
    }

    public function notificarRequerimientoPresentado(
        Expediente $expediente,
        Cliente $cliente,
        string $requerimientoNombre,
    ): void {
        $this->despachar->despachar(
            $expediente,
            'requerimiento_presentado',
            sprintf('Requerimiento presentado — Expediente %s', $expediente->numero()),
            sprintf(
                "Su abogado ha presentado ante la Administración el requerimiento «%s» "
                . "del expediente %s.\n\n"
                . "Puede consultar el detalle en su portal:\n%s",
                $requerimientoNombre,
                $expediente->numero(),
                $this->accessUrl($expediente),
            ),
            'notificacion_requerimiento_presentado',
            'Se ha notificado al cliente la presentación del requerimiento por %s.',
        );
    }

    private function accessUrl(Expediente $expediente): string
    {
        return rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
    }
}
