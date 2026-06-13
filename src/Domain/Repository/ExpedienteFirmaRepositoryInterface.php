<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedienteFirmaDocumento;
use App\Domain\Entity\TipoEscrito;
use App\Domain\ValueObject\ExpedienteId;

interface ExpedienteFirmaRepositoryInterface
{
    public function save(ExpedienteFirmaDocumento $firma): void;

    /**
     * @return ExpedienteFirmaDocumento[]
     */
    public function findByExpediente(ExpedienteId $expedienteId): array;

    public function findByExpedienteAndTipo(ExpedienteId $expedienteId, TipoEscrito $tipo): ?ExpedienteFirmaDocumento;
}
