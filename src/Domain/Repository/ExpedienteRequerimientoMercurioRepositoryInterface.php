<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedienteRequerimientoMercurio;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

interface ExpedienteRequerimientoMercurioRepositoryInterface
{
    public function save(ExpedienteRequerimientoMercurio $requerimiento): void;

    /**
     * @return list<ExpedienteRequerimientoMercurio>
     */
    public function findByExpediente(ExpedienteId $expedienteId): array;

    public function findById(ExpedienteRequerimientoMercurioId $id): ?ExpedienteRequerimientoMercurio;

    public function countAbiertosByExpediente(ExpedienteId $expedienteId): int;
}
