<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\DespachoConfig;
use App\Domain\Repository\DespachoConfigRepositoryInterface;

final class ObtenerDespachoConfigUseCase
{
    public function __construct(
        private DespachoConfigRepositoryInterface $repository,
    ) {
    }

    public function __invoke(): DespachoConfig
    {
        $config = $this->repository->find();

        if (null === $config) {
            $config = DespachoConfig::defaults();
            $this->repository->save($config);
        }

        return $config;
    }
}
