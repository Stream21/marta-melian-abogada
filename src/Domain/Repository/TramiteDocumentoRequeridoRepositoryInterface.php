<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\TramiteDocumentoRequerido;
use App\Domain\ValueObject\TramiteId;

interface TramiteDocumentoRequeridoRepositoryInterface
{
    /**
     * @return list<TramiteDocumentoRequerido>
     */
    public function findByTramiteId(TramiteId $tramiteId): array;

    /**
     * @param list<TramiteDocumentoRequerido> $documentos
     */
    public function replaceForTramite(TramiteId $tramiteId, array $documentos): void;
}
