<?php

declare(strict_types=1);

namespace App\Application\Port;

interface EmailPort
{
    public function isConfigured(): bool;

    public function send(string $to, string $subject, string $body): void;
}
