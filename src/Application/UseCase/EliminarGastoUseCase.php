<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\GastoFileStoragePort;
use App\Domain\Repository\GastoRepositoryInterface;
use App\Domain\ValueObject\GastoId;

final class EliminarGastoUseCase
{
    public function __construct(
        private GastoRepositoryInterface $gastoRepository,
        private GastoFileStoragePort $fileStorage,
    ) {
    }

    public function __invoke(string $id): void
    {
        $gastoId = new GastoId($id);
        $gasto = $this->gastoRepository->findById($gastoId);
        if (null === $gasto) {
            throw new \InvalidArgumentException('Gasto no encontrado.');
        }

        $this->fileStorage->deleteFolder($gastoId);
        $this->gastoRepository->delete($gastoId);
    }
}
