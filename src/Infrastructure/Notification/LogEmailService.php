<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Application\Port\EmailPort;
use Psr\Log\LoggerInterface;

final class LogEmailService implements EmailPort
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function send(string $to, string $subject, string $body): void
    {
        $this->logger->info('[EMAIL-SIM] Correo enviado', [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
        ]);
    }
}
