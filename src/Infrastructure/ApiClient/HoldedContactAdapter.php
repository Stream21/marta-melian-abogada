<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiClient;

use App\Application\Port\HoldedContactPort;

final class HoldedContactAdapter implements HoldedContactPort
{
    public function __construct(
        private HoldedApiClient $holdedApiClient,
    ) {
    }

    public function createContact(string $nombre, string $email, string $codigoFiscal, string $telefono = ''): string
    {
        unset($telefono);

        return $this->holdedApiClient->createContact($nombre, $email, $codigoFiscal);
    }
}
