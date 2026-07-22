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

    public function assertTelefonoUnico(?string $telefono, ?string $excludeClienteId, bool $permitirDuplicado = false): void
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

        if ($permitirDuplicado) {
            // Liberar el teléfono del otro cliente (NULL en BD) para poder reasignarlo.
            $this->repository->save($existente->withoutTelefono());

            return;
        }

        throw new TelefonoClienteDuplicadoException(
            $telefono,
            $existente->id()->value(),
            $existente->nombre(),
        );
    }

    public function assertDocumentoUnico(string $numDocumento, ?string $excludeClienteId, bool $permitirDuplicado = false): void
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

        if ($permitirDuplicado) {
            return;
        }

        throw new DocumentoIdentidadClienteDuplicadoException(
            $numDocumento,
            $existente->id()->value(),
            $existente->nombre(),
        );
    }
}
