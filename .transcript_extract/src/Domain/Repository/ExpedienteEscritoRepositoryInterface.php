<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedienteEscrito;
use App\Domain\ValueObject\ExpedienteId;

interface ExpedienteEscritoRepositoryInterface
{
    public function save(ExpedienteEscrito $escrito): void;

    /**
     * @return ExpedienteEscrito[]
     */
    public function findByExpediente(ExpedienteId $expedienteId): array;

    public function findById(string $id): ?ExpedienteEscrito;
}
