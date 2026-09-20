<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteDocumentoRequerido;

final class NotificarExpedienteClienteService
{
    public function __construct(
        private DespacharNotificacionClienteService $despachar,
        private string $frontendBaseUrl,
    ) {
    }

    public function notificarDocumentoDevuelto(
        Expediente $expediente,
        ExpedienteDocumentoRequerido $documento,
        string $nota,
    ): void {
        $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        $detalle = '' !== trim($nota)
            ? trim($nota)
            : 'Revise el documento indicado y vuelva a subirlo correctamente.';
        $mensaje = sprintf(
            "Su abogado ha devuelto el documento «%s» del expediente %s:\n\n%s\n\nAcceda a su portal para volver a subirlo:\n%s",
            $documento->nombre(),
            $expediente->numero(),
            $detalle,
            $accessUrl,
        );

        $this->despachar->despachar(
            $expediente,
            'documento_devuelto',
            sprintf('Documento devuelto — Expediente %s', $expediente->numero()),
            $mensaje,
            'notificacion_documento_devuelto',
            sprintf(
                'Se ha notificado al cliente la devolución del documento «%s» por %%s.',
                $documento->nombre(),
            ),
        );
    }

    public function notificarNuevoDocumentoRequerido(
        Expediente $expediente,
        ExpedienteDocumentoRequerido $documento,
    ): void {
        $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        $mensaje = sprintf(
            "Su abogado le ha solicitado un nuevo documento para el expediente %s:\n\n«%s»\n%s\n\nAcceda a su portal para subirlo:\n%s",
            $expediente->numero(),
            $documento->nombre(),
            '' !== trim($documento->descripcion()) ? "\n" . $documento->descripcion() : '',
            $accessUrl,
        );

        $this->despachar->despachar(
            $expediente,
            'nuevo_documento_requerido',
            sprintf('Nuevo documento requerido — Expediente %s', $expediente->numero()),
            $mensaje,
            'notificacion_nuevo_documento',
            sprintf(
                'Se ha notificado al cliente el nuevo documento requerido «%s» por %%s.',
                $documento->nombre(),
            ),
        );
    }
}
