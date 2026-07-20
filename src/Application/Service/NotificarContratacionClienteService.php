<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\EmailPort;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\PasoContratacionCliente;
use Psr\Log\LoggerInterface;

final class NotificarContratacionClienteService
{
    public function __construct(
        private EmailPort $emailPort,
        private LoggerInterface $logger,
        private string $frontendBaseUrl,
    ) {
    }

    public function notificarAccionRequerida(
        Expediente $expediente,
        Cliente $cliente,
        PasoContratacionCliente $paso,
        string $nota,
    ): bool {
        $email = trim($cliente->email());
        if ('' === $email) {
            $this->logger->warning('No se pudo notificar al cliente por email: sin correo registrado.', [
                'expediente' => $expediente->numero(),
                'paso' => $paso->value,
            ]);

            return false;
        }

        $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        $mensaje = sprintf(
            "Su abogado le ha enviado un mensaje sobre el paso «%s» de su expediente %s:\n\n%s\n\nAcceda a su portal para continuar:\n%s",
            $paso->label(),
            $expediente->numero(),
            $nota,
            $accessUrl,
        );

        try {
            $this->emailPort->send(
                $email,
                sprintf('Acción requerida — Expediente %s', $expediente->numero()),
                $mensaje,
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error enviando notificación de contratación al cliente', [
                'email' => $email,
                'expediente' => $expediente->numero(),
                'paso' => $paso->value,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
