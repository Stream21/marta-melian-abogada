<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedienteNota;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteNotaId;

interface ExpedienteNotaRepositoryInterface
{
    public function save(ExpedienteNota $nota): void;

    public function findById(ExpedienteNotaId $id): ?ExpedienteNota;

    /**
     * Notas del expediente, de más reciente a más antigua.
     *
     * @return list<ExpedienteNota>
     */
    public function findByExpediente(ExpedienteId $expedienteId): array;

/**
 * Resumen para listado: notas activas + última nota activa (más reciente).
 *
 * @param list<string> $expedienteIds
 *
 * @return array<string, array{activas: int, ultima: ?ExpedienteNota}>
 */
public function resumenPorExpedientes(array $expedienteIds): array;
}
