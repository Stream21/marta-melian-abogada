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
        $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
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
}
