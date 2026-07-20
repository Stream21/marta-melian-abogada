<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;

final class EliminarClienteUseCase
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
    ) {
    }

    public function __invoke(string $id): void
    {
        $clienteId = new ClienteId($id);
        $cliente = $this->clienteRepository->findById($clienteId);

        if (null === $cliente) {
            throw new \InvalidArgumentException('Cliente no encontrado.');
        }

        if ([] !== $this->expedienteRepository->findByClienteId($clienteId)) {
            throw new \DomainException('No se puede eliminar un cliente con expedientes asociados.');
        }

        $this->clienteRepository->delete($clienteId);
    }
}
