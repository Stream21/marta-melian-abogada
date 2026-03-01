<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Expediente;
use App\Domain\ValueObject\ExpedienteId;

interface ExpedienteRepositoryInterface
{
    public function save(Expediente $expediente): void;

    public function findById(ExpedienteId $id): ?Expediente;

    /**
     * @return Expediente[]
     */
    public function findAll(): array;

    public function remove(Expediente $expediente): void;
}
