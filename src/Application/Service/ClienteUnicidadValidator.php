<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Exception\DocumentoIdentidadClienteDuplicadoException;
use App\Domain\Exception\TelefonoClienteDuplicadoException;
use App\Domain\Repository\ClienteRepositoryInterface;

final class ClienteUnicidadValidator
{
    public function __construct(
        private ClienteRepositoryInterface $repository,
    ) {
    }

    public function assertTelefonoUnico(?string $telefono, ?string $excludeClienteId): void
    {
        if (null === $telefono || '' === $telefono) {
            return;
        }

        $existente = $this->repository->findByTelefono($telefono);
        if (null === $existente) {
            return;
        }

        if (null !== $excludeClienteId && $excludeClienteId === $existente->id()->value()) {
            return;
        }

        throw new TelefonoClienteDuplicadoException(
            $telefono,
            $existente->id()->value(),
            $existente->nombre(),
        );
    }

    public function assertDocumentoUnico(string $numDocumento, ?string $excludeClienteId): void
    {
        if ('' === $numDocumento) {
            return;
        }

        $existente = $this->repository->findByNumDocumento($numDocumento);
        if (null === $existente) {
            return;
        }

        if (null !== $excludeClienteId && $excludeClienteId === $existente->id()->value()) {
            return;
        }

        throw new DocumentoIdentidadClienteDuplicadoException(
            $numDocumento,
            $existente->id()->value(),
            $existente->nombre(),
        );
    }
}
