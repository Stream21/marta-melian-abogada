<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedientePresentacionTelematica;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedientePresentacionTelematicaId;

interface ExpedientePresentacionTelematicaRepositoryInterface
{
    public function save(ExpedientePresentacionTelematica $presentacion): void;

    public function findByExpediente(ExpedienteId $expedienteId): ?ExpedientePresentacionTelematica;

    public function findById(ExpedientePresentacionTelematicaId $id): ?ExpedientePresentacionTelematica;
}
