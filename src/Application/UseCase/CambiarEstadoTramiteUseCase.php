<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Tramite;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteId;

final class CambiarEstadoTramiteUseCase
{
    public function __construct(
        private TramiteRepositoryInterface $repository,
    ) {
    }

    public function __invoke(string $id, bool $activo): Tramite
    {
        $tramiteId = new TramiteId($id);
        $existing = $this->repository->findById($tramiteId);
        if (null === $existing) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $updated = $existing->withActivo($activo);
        $this->repository->save($updated);

        return $this->repository->findById($tramiteId) ?? $updated;
    }
}
