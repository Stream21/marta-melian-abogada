<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\NacionalidadRepositoryInterface;

final class ListarNacionalidadesUseCase
{
    public function __construct(
        private NacionalidadRepositoryInterface $nacionalidadRepository,
    ) {
    }

    /**
     * @return list<array{codigo: string, nombre: string}>
     */
    public function __invoke(): array
    {
        $items = [];
        foreach ($this->nacionalidadRepository->findAllActivas() as $nacionalidad) {
            $items[] = [
                'codigo' => $nacionalidad->codigo,
                'nombre' => $nacionalidad->nombre,
            ];
        }

        return $items;
    }
}
