<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\EmailPort;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteDocumentoRequerido;
use Psr\Log\LoggerInterface;

final class NotificarExpedienteClienteService
{
    public function __construct(
        private EmailPort $emailPort,
        private LoggerInterface $logger,
        private string $frontendBaseUrl,
    ) {
    }

    public function notificarDocumentoDevuelto(
        Expediente $expediente,
        string $clienteEmail,
        ExpedienteDocumentoRequerido $documento,
        string $nota,
    ): bool {
        $email = trim($clienteEmail);
        if ('' === $email) {
            return false;
        }

        $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        $mensaje = sprintf(
            "Su abogado ha devuelto el documento «%s» del expediente %s:\n\n%s\n\nAcceda a su portal para volver a subirlo:\n%s",
            $documento->nombre(),
            $expediente->numero(),
            $nota,
            $accessUrl,
        );

        return $this->enviar($email, sprintf('Documento devuelto — Expediente %s', $expediente->numero()), $mensaje, [
            'expediente' => $expediente->numero(),
            'documento' => $documento->nombre(),
        ]);
    }

    public function notificarNuevoDocumentoRequerido(
        Expediente $expediente,
        string $clienteEmail,
        ExpedienteDocumentoRequerido $documento,
    ): bool {
        $email = trim($clienteEmail);
        if ('' === $email) {
            return false;
        }

        $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        $mensaje = sprintf(
            "Su abogado le ha solicitado un nuevo documento para el expediente %s:\n\n«%s»\n%s\n\nAcceda a su portal para subirlo:\n%s",
            $expediente->numero(),
            $documento->nombre(),
            '' !== trim($documento->descripcion()) ? "\n" . $documento->descripcion() : '',
            $accessUrl,
        );

        return $this->enviar($email, sprintf('Nuevo documento requerido — Expediente %s', $expediente->numero()), $mensaje, [
            'expediente' => $expediente->numero(),
            'documento' => $documento->nombre(),
        ]);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function enviar(string $email, string $subject, string $body, array $context): bool
    {
        try {
            $this->emailPort->send($email, $subject, $body);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error enviando notificación al cliente', [
                ...$context,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
