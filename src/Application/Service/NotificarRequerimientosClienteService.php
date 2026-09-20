<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;

final class NotificarRequerimientosClienteService
{
    public function __construct(
        private DespacharNotificacionClienteService $despachar,
        private string $frontendBaseUrl,
    ) {
    }

    public function notificarDocumentoDerivado(
        Expediente $expediente,
        Cliente $cliente,
        string $documentoNombre,
        string $nota = '',
    ): void {
        $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        $cuerpoNota = '' !== trim($nota)
            ? sprintf("\n\nMensaje de su abogado:\n%s", trim($nota))
            : '';

        $mensaje = sprintf(
            "Su abogado le ha solicitado completar el documento «%s» del expediente %s.%s\n\nAcceda a su portal para continuar:\n%s",
            $documentoNombre,
            $expediente->numero(),
            $cuerpoNota,
            $accessUrl,
        );

        $this->despachar->despachar(
            $expediente,
            'documento_derivado',
            sprintf('Documentación requerida — Expediente %s', $expediente->numero()),
            $mensaje,
            'notificacion_documento_derivado',
            sprintf(
                'Se ha notificado al cliente la derivación del documento «%s» por %%s.',
                $documentoNombre,
            ),
        );
    }
}
