<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;

interface ExpedienteDocumentoRepositoryInterface
{
    public function save(ExpedienteDocumentoEntregado $documento): void;

    /**
     * @return ExpedienteDocumentoEntregado[]
     */
    public function findByExpediente(ExpedienteId $expedienteId): array;

    /**
     * @param list<string> $expedienteIds
     *
     * @return array<string, int>
     */
    public function countPendientesRevisionByExpedienteIds(array $expedienteIds): array;

    public function findByExpedienteAndDocumento(
        ExpedienteId $expedienteId,
        TramiteDocumentoRequeridoId $documentoRequeridoId,
    ): ?ExpedienteDocumentoEntregado;

    public function findByExpedienteAndExpedienteDocumento(
        ExpedienteId $expedienteId,
        ExpedienteDocumentoRequeridoId $expedienteDocumentoRequeridoId,
    ): ?ExpedienteDocumentoEntregado;

    public function deleteById(string $id): void;
}
