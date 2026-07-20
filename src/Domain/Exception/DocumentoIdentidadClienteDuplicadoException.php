<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class DocumentoIdentidadClienteDuplicadoException extends \DomainException implements ClienteDuplicadoExceptionInterface
{
    public function __construct(
        public readonly string $numDocumento,
        private readonly string $clienteExistenteId,
        private readonly string $clienteExistenteNombre,
    ) {
        parent::__construct('Ya existe un cliente con este documento de identidad.');
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
