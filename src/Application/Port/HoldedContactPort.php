<?php

declare(strict_types=1);

namespace App\Application\Port;

interface HoldedContactPort
{
    /**
     * Crea un contacto en Holded y devuelve su identificador.
     */
    public function createContact(string $nombre, string $email, string $codigoFiscal, string $telefono = ''): string;
}
