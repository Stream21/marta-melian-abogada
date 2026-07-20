<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

interface ExpedienteDocumentoRequeridoRepositoryInterface
{
    public function save(ExpedienteDocumentoRequerido $documento): void;

    /**
     * @return ExpedienteDocumentoRequerido[]
     */
    public function findByExpediente(ExpedienteId $expedienteId): array;

    public function findById(ExpedienteDocumentoRequeridoId $id): ?ExpedienteDocumentoRequerido;

    public function countByExpediente(ExpedienteId $expedienteId): int;
}
