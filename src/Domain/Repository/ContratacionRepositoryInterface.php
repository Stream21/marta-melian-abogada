<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ContratacionPaso;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\ValueObject\ExpedienteId;

interface ContratacionRepositoryInterface
{
    public function savePaso(ContratacionPaso $paso): void;

    /**
     * @return ContratacionPaso[]
     */
    public function findPasosByExpediente(ExpedienteId $expedienteId): array;

    public function findPaso(ExpedienteId $expedienteId, PasoContratacionCliente $paso): ?ContratacionPaso;

    public function saveHito(ExpedienteHito $hito): void;

    /**
     * @return ExpedienteHito[]
     */
    public function findHitosByExpediente(ExpedienteId $expedienteId): array;

    /**
     * @return ExpedienteHito[]
     */
    public function findRecentHitos(int $limit = 20): array;

    /**
     * @return ExpedienteHito[]
     */
    public function findHitosByExpedientePaginated(ExpedienteId $expedienteId, int $offset, int $limit): array;

    public function countHitosByExpediente(ExpedienteId $expedienteId): int;
}
