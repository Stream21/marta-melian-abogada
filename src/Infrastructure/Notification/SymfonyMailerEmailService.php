<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Application\Port\EmailPort;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class SymfonyMailerEmailService implements EmailPort
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromAddress,
        private string $fromName,
        private string $mailerDsn,
        private LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== trim($this->fromAddress) && $this->hasTransportDsn();
    }

    public function send(string $to, string $subject, string $body): void
    {
        $to = trim($to);
        if ('' === $to) {
            throw new \InvalidArgumentException('El destinatario del correo es obligatorio.');
        }

        if (!filter_var($to, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf('Email de destino no válido: %s', $to));
        }

        if (!$this->isConfigured()) {
            throw new \RuntimeException(
                'Correo no configurado. Revise MAILER_DSN y MAILER_FROM en .env y reinicie el contenedor php.',
            );
        }

        $message = (new Email())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($to)
            ->subject($subject)
            ->text($body);

        try {
            $this->mailer->send($message);
            $this->logger->info('Correo enviado', ['to' => $to, 'subject' => $subject]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Error al enviar correo', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('No se pudo enviar el correo: ' . $e->getMessage(), 0, $e);
        }
    }

    private function hasTransportDsn(): bool
    {
        $dsn = trim($this->mailerDsn);

        return '' !== $dsn && 'null://null' !== $dsn;
    }
}
