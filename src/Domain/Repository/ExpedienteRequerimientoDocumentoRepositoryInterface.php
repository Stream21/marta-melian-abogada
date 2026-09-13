<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedienteRequerimientoDocumento;
use App\Domain\ValueObject\ExpedienteRequerimientoDocumentoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

interface ExpedienteRequerimientoDocumentoRepositoryInterface
{
    public function save(ExpedienteRequerimientoDocumento $documento): void;

    /**
     * @param list<ExpedienteRequerimientoDocumento> $documentos
     */
    public function saveAll(array $documentos): void;

    public function findById(ExpedienteRequerimientoDocumentoId $id): ?ExpedienteRequerimientoDocumento;

    /**
     * @return list<ExpedienteRequerimientoDocumento>
     */
    public function findByRequerimientoId(ExpedienteRequerimientoMercurioId $requerimientoId): array;

    public function delete(ExpedienteRequerimientoDocumentoId $id): void;
}
