<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\EmailPort;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;
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
        Cliente $cliente,
        string $nombreDocumento,
        string $nota,
    ): bool {
        return $this->enviar(
            $expediente,
            $cliente,
            sprintf('Documento devuelto — Expediente %s', $expediente->numero()),
            sprintf(
                "Su abogado ha solicitado revisar el documento «%s» del expediente %s:\n\n%s\n\nAcceda a su portal para volver a subirlo:\n%s",
                $nombreDocumento,
                $expediente->numero(),
                $nota,
                $this->portalUrl($expediente),
            ),
            'documento_devuelto',
        );
    }

    public function notificarNuevoDocumentoRequerido(
        Expediente $expediente,
        Cliente $cliente,
        string $nombreDocumento,
        string $descripcion,
    ): bool {
        $cuerpo = sprintf(
            "Su abogado ha solicitado un nuevo documento para su expediente %s:\n\n«%s»",
            $expediente->numero(),
            $nombreDocumento,
        );
        if ('' !== trim($descripcion)) {
            $cuerpo .= "\n\n" . trim($descripcion);
        }
        $cuerpo .= "\n\nAcceda a su portal para subirlo:\n" . $this->portalUrl($expediente);

        return $this->enviar(
            $expediente,
            $cliente,
            sprintf('Nuevo documento requerido — Expediente %s', $expediente->numero()),
            $cuerpo,
            'documento_requerido_anadido',
        );
    }

    private function enviar(Expediente $expediente, Cliente $cliente, string $asunto, string $mensaje, string $contexto): bool
    {
        $email = trim($cliente->email());
        if ('' === $email) {
            $this->logger->warning('No se pudo notificar al cliente por email: sin correo registrado.', [
                'expediente' => $expediente->numero(),
                'contexto' => $contexto,
            ]);

            return false;
        }

        try {
            $this->emailPort->send($email, $asunto, $mensaje);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error enviando notificación al cliente', [
                'email' => $email,
                'expediente' => $expediente->numero(),
                'contexto' => $contexto,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function portalUrl(Expediente $expediente): string
    {
        return rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
    }
}
