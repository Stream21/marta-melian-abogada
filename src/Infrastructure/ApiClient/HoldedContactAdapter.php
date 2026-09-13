<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiClient;

use App\Application\DTO\Holded\ClienteHoldedData;
use App\Application\Port\HoldedContactPort;
use App\Application\Port\HoldedPort;

/**
 * Adaptador legacy: crea contactos vía el puerto unificado HoldedPort.
 */
final class HoldedContactAdapter implements HoldedContactPort
{
    public function __construct(
        private HoldedPort $holdedPort,
    ) {
    }

    public function createContact(string $nombre, string $email, string $codigoFiscal, string $telefono = ''): string
    {
        return $this->holdedPort->findOrCreateContact(new ClienteHoldedData(
            name: $nombre,
            email: $email,
            documentNumber: $codigoFiscal,
            phone: $telefono,
        ));
    }
}
