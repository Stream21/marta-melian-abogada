<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ClienteResponseMapper;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
final class ListarClientesUseCase
{
    public function __construct(
        private ClienteRepositoryInterface $repository,
        private ExpedienteRepositoryInterface $expedienteRepository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(): array
    {
        $clientes = $this->repository->findAll();

        return array_map(function ($cliente) {
            $numExpedientes = count($this->expedienteRepository->findByClienteId($cliente->id()));

            return ClienteResponseMapper::fromDomain($cliente, $numExpedientes);
        }, $clientes);
    }
}
