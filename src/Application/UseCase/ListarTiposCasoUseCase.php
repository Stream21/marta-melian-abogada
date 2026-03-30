<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\TipoCaso;
use App\Domain\Repository\TipoCasoRepositoryInterface;

final class ListarTiposCasoUseCase
{
    public function __construct(
        private TipoCasoRepositoryInterface $repository,
    ) {
    }

    /**
     * @return TipoCaso[]
     */
    public function __invoke(): array
    {
        return $this->repository->findAll();
    }
}
