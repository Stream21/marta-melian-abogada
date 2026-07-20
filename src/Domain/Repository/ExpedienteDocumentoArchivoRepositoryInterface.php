<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedienteDocumentoArchivo;

interface ExpedienteDocumentoArchivoRepositoryInterface
{
    /**
     * @param list<ExpedienteDocumentoArchivo> $archivos
     */
    public function saveMany(array $archivos): void;

    public function deleteByEntregadoId(string $entregadoId): void;

    /**
     * @return list<ExpedienteDocumentoArchivo>
     */
    public function findByEntregadoId(string $entregadoId): array;

    /**
     * @param list<string> $entregadoIds
     *
     * @return array<string, list<ExpedienteDocumentoArchivo>> keyed by entregadoId
     */
    public function findByEntregadoIds(array $entregadoIds): array;

    public function findById(string $id): ?ExpedienteDocumentoArchivo;
}
