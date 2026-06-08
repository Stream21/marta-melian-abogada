<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Servicio;
use App\Domain\Repository\ServicioRepositoryInterface;

final class ListarServiciosUseCase
{
    public function __construct(
        private ServicioRepositoryInterface $repository,
    ) {
    }

    /**
     * @return Servicio[]
     */
    public function __invoke(bool $incluirInactivos = false): array
    {
        return $this->repository->findAll($incluirInactivos);
    }
}
