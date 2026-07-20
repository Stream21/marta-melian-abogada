<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\EmailPort;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;
use Psr\Log\LoggerInterface;

final class NotificarRequerimientosClienteService
{
    public function __construct(
        private EmailPort $emailPort,
        private LoggerInterface $logger,
        private string $frontendBaseUrl,
    ) {
    }

    public function notificarDocumentoDerivado(
        Expediente $expediente,
        Cliente $cliente,
        string $documentoNombre,
        string $nota = '',
    ): bool {
        $email = trim($cliente->email());
        if ('' === $email) {
            $this->logger->warning('No se pudo notificar al cliente por email: sin correo registrado.', [
                'expediente' => $expediente->numero(),
                'documento' => $documentoNombre,
            ]);

            return false;
        }

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

        try {
            $this->emailPort->send(
                $email,
                sprintf('Documentación requerida — Expediente %s', $expediente->numero()),
                $mensaje,
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error enviando notificación de requerimientos al cliente', [
                'email' => $email,
                'expediente' => $expediente->numero(),
                'documento' => $documentoNombre,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
