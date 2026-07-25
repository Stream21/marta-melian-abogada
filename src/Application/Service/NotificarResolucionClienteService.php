<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\EmailPort;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\OutcomeResolucion;
use Psr\Log\LoggerInterface;

final class NotificarResolucionClienteService
{
    public function __construct(
        private EmailPort $emailPort,
        private LoggerInterface $logger,
        private string $frontendBaseUrl,
    ) {
    }

    public function notificarResolucionRegistrada(
        Expediente $expediente,
        Cliente $cliente,
        OutcomeResolucion $outcome,
    ): bool {
        $sentido = OutcomeResolucion::Concedida === $outcome
            ? 'favorable (concedida)'
            : 'desfavorable (denegada)';

        $extra = OutcomeResolucion::Concedida === $outcome
            ? "En su portal encontrará los pasos siguientes (cita TIE, tasas, modelos u otras gestiones según su caso).\n\n"
            : "Consulte la resolución en su portal y hable con su abogado sobre posibles recursos.\n\n";

        return $this->enviar(
            $cliente,
            sprintf('Resolución %s — Expediente %s', $outcome->label(), $expediente->numero()),
            sprintf(
                "Se ha registrado la resolución administrativa de su expediente %s: %s.\n\n"
                . '%s'
                . "Portal:\n%s",
                $expediente->numero(),
                $sentido,
                $extra,
                $this->accessUrl($expediente),
            ),
            $expediente->numero(),
            'resolucion',
        );
    }

    public function notificarRecordatorioFuturoCliente(
        Expediente $expediente,
        Cliente $cliente,
        string $motivo,
        \DateTimeImmutable $fecha,
    ): bool {
        return $this->enviar(
            $cliente,
            sprintf('Recordatorio — Expediente %s', $expediente->numero()),
            sprintf(
                "Le recordamos una gestión pendiente relacionada con el expediente %s.\n\n"
                . "Motivo: %s\n"
                . "Fecha prevista: %s\n\n"
                . "Contacte con su abogado si desea iniciar el nuevo trámite.\n\n"
                . "Portal (si el enlace sigue activo):\n%s",
                $expediente->numero(),
                $motivo,
                $fecha->format('d/m/Y'),
                $this->accessUrl($expediente),
            ),
            $expediente->numero(),
            'recordatorio_cliente',
        );
    }

    public function notificarRecordatorioFuturoDespacho(
        string $emailDespacho,
        Expediente $expediente,
        string $motivo,
        \DateTimeImmutable $fecha,
        string $clienteNombre,
    ): bool {
        $email = trim($emailDespacho);
        if ('' === $email) {
            $this->logger->warning('Recordatorio despacho sin email de configuración.', [
                'expediente' => $expediente->numero(),
            ]);

            return false;
        }

        try {
            $this->emailPort->send(
                $email,
                sprintf('Recordatorio cliente futuro — %s', $expediente->numero()),
                sprintf(
                    "Recordatorio de seguimiento comercial / renovación.\n\n"
                    . "Expediente: %s\n"
                    . "Cliente: %s\n"
                    . "Motivo: %s\n"
                    . "Fecha: %s\n",
                    $expediente->numero(),
                    $clienteNombre,
                    $motivo,
                    $fecha->format('d/m/Y'),
                ),
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error enviando recordatorio a despacho', [
                'expediente' => $expediente->numero(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function accessUrl(Expediente $expediente): string
    {
        return rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
    }

    private function enviar(
        Cliente $cliente,
        string $asunto,
        string $mensaje,
        string $expedienteNumero,
        string $contexto,
    ): bool {
        $email = trim($cliente->email());
        if ('' === $email) {
            $this->logger->warning('No se pudo notificar resolución al cliente: sin correo.', [
                'expediente' => $expedienteNumero,
                'contexto' => $contexto,
            ]);

            return false;
        }

        try {
            $this->emailPort->send($email, $asunto, $mensaje);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error enviando notificación de resolución', [
                'email' => $email,
                'expediente' => $expedienteNumero,
                'contexto' => $contexto,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
