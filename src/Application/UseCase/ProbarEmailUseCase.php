<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\EmailPort;

final class ProbarEmailUseCase
{
    public function __construct(
        private EmailPort $emailPort,
        private string $mailerDsn,
    ) {
    }

    /**
     * @return array{enviado: bool}
     */
    public function __invoke(string $email, ?string $asunto = null, ?string $mensaje = null): array
    {
        $email = trim($email);
        if ('' === $email) {
            throw new \InvalidArgumentException('El email de destino es obligatorio.');
        }

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email de destino no válido.');
        }

        if (!$this->emailPort->isConfigured()) {
            throw new \InvalidArgumentException(
                'Correo no configurado. Defina MAILER_DSN y MAILER_FROM en .env (en desarrollo: smtp://mailpit:1025).',
            );
        }

        $this->emailPort->send(
            $email,
            null !== $asunto && '' !== trim($asunto)
                ? trim($asunto)
                : 'Prueba de correo — Bufete Melián',
            null !== $mensaje && '' !== trim($mensaje)
                ? trim($mensaje)
                : 'Prueba de integración de correo desde la aplicación del bufete.',
        );

        return ['enviado' => true];
    }

    /**
     * @return array{configurado: bool, capturaLocal: bool, bandejaUrl: string|null}
     */
    public function estado(): array
    {
        $capturaLocal = $this->usaCapturaLocal();

        return [
            'configurado' => $this->emailPort->isConfigured(),
            'capturaLocal' => $capturaLocal,
            'bandejaUrl' => $capturaLocal ? 'http://localhost:8025' : null,
        ];
    }

    private function usaCapturaLocal(): bool
    {
        $dsn = strtolower(trim($this->mailerDsn));

        if ('' === $dsn || 'null://null' === $dsn) {
            return false;
        }

        return str_contains($dsn, 'mailpit')
            || str_contains($dsn, 'localhost:1025')
            || str_contains($dsn, '127.0.0.1:1025');
    }
}
