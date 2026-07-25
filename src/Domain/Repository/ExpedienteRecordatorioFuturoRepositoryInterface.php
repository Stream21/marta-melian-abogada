<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedienteRecordatorioFuturo;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRecordatorioFuturoId;

interface ExpedienteRecordatorioFuturoRepositoryInterface
{
    public function save(ExpedienteRecordatorioFuturo $recordatorio): void;

    public function findByExpediente(ExpedienteId $expedienteId): ?ExpedienteRecordatorioFuturo;

    public function findById(ExpedienteRecordatorioFuturoId $id): ?ExpedienteRecordatorioFuturo;

    /**
     * @return list<ExpedienteRecordatorioFuturo>
     */
    public function findPendientesHasta(\DateTimeImmutable $hasta): array;
}
