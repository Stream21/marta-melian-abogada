<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedienteResolucion;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteResolucionId;

interface ExpedienteResolucionRepositoryInterface
{
    public function save(ExpedienteResolucion $resolucion): void;

    public function findByExpediente(ExpedienteId $expedienteId): ?ExpedienteResolucion;

    public function findById(ExpedienteResolucionId $id): ?ExpedienteResolucion;
}
