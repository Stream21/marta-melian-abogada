<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class TelefonoClienteDuplicadoException extends \DomainException implements ClienteDuplicadoExceptionInterface
{
    public function __construct(
        public readonly string $telefono,
        private readonly string $clienteExistenteId,
        private readonly string $clienteExistenteNombre,
    ) {
        parent::__construct('Ya existe un cliente con este teléfono móvil.');
    }

    public function clienteExistenteId(): string
    {
        return $this->clienteExistenteId;
    }

    public function clienteExistenteNombre(): string
    {
        return $this->clienteExistenteNombre;
    }
}
