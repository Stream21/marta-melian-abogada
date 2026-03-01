<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ExpedienteRepositoryInterface;

final class ListarExpedientesUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
    ) {
    }

    /**
     * @return \App\Domain\Entity\Expediente[]
     */
    public function __invoke(): array
    {
        return $this->expedienteRepository->findAll();
    }
}
