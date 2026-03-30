<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\TipoCasoRepositoryInterface;
use App\Domain\ValueObject\TipoCasoId;

final class EliminarTipoCasoUseCase
{
    public function __construct(
        private TipoCasoRepositoryInterface $repository,
    ) {
    }

    public function __invoke(string $id): void
    {
        $tipoCasoId = new TipoCasoId($id);
        $existing = $this->repository->findById($tipoCasoId);
        if (null === $existing) {
            throw new \InvalidArgumentException('Tipo de caso no encontrado.');
        }
        $this->repository->remove($existing);
    }
}
