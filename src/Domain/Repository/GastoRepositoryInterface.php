<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Gasto;
use App\Domain\ValueObject\GastoId;

interface GastoRepositoryInterface
{
    public function save(Gasto $gasto): void;

    public function findById(GastoId $id): ?Gasto;

    /**
     * @return Gasto[]
     */
    public function findAll(): array;

    public function delete(GastoId $id): void;
}
