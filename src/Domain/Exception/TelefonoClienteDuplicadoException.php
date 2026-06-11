<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class TelefonoClienteDuplicadoException extends \DomainException
{
    public function __construct(
        public readonly string $telefono,
        public readonly string $clienteExistenteId,
        public readonly string $clienteExistenteNombre,
    ) {
        parent::__construct('Ya existe un cliente con este teléfono.');
    }
}
