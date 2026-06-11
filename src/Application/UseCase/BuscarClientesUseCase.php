<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Cliente;
use App\Domain\Repository\ClienteRepositoryInterface;

final class BuscarClientesUseCase
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
    ) {
    }

    /**
     * @return array{clientes: array<int, array<string, mixed>>}
     */
    public function __invoke(string $query): array
    {
        $trimmed = trim($query);
        if ('' === $trimmed) {
            return ['clientes' => []];
        }

        $clientes = $this->clienteRepository->search($trimmed);

        return [
            'clientes' => array_map($this->clienteToArray(...), $clientes),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function clienteToArray(Cliente $cliente): array
    {
        return [
            'id' => $cliente->id()->value(),
            'nombre' => $cliente->nombre(),
            'telefono' => $cliente->telefono(),
            'email' => $cliente->email(),
            'tipoDocumento' => $cliente->tipoDocumento(),
            'numDocumento' => $cliente->numDocumento(),
        ];
    }
}
