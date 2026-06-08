<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Servicio;
use App\Domain\Repository\ServicioRepositoryInterface;
use App\Domain\ValueObject\ServicioId;

final class CambiarEstadoServicioUseCase
{
    public function __construct(
        private ServicioRepositoryInterface $repository,
    ) {
    }

    public function __invoke(string $id, bool $activo): Servicio
    {
        $servicioId = new ServicioId($id);
        $existing = $this->repository->findById($servicioId);
        if (null === $existing) {
            throw new \InvalidArgumentException('Servicio no encontrado.');
        }

        $updated = $existing->withActivo($activo);
        $this->repository->save($updated);

        return $this->repository->findById($servicioId) ?? $updated;
    }
}
