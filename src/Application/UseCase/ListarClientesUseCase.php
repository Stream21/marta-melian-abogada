<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ClienteRepositoryInterface;

final class ListarClientesUseCase
{
    public function __construct(
        private ClienteRepositoryInterface $repository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(): array
    {
        return array_map(
            static fn ($cliente) => [
                'id' => $cliente->id()->value(),
                'nombre' => $cliente->nombre(),
                'nacionalidad' => $cliente->nacionalidad(),
                'tipoDocumento' => $cliente->tipoDocumento(),
                'numDocumento' => $cliente->numDocumento(),
                'fechaNacimiento' => $cliente->fechaNacimiento()?->format('Y-m-d'),
                'lugarNacimiento' => $cliente->lugarNacimiento(),
                'domicilio' => $cliente->domicilio(),
                'codigoPostal' => $cliente->codigoPostal(),
                'ciudad' => $cliente->ciudad(),
                'telefono' => $cliente->telefono(),
                'email' => $cliente->email(),
            ],
            $this->repository->findAll(),
        );
    }
}
