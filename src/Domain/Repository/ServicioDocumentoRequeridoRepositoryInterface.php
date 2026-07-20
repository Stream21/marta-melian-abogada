<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ServicioDocumentoRequerido;
use App\Domain\ValueObject\ServicioId;

interface ServicioDocumentoRequeridoRepositoryInterface
{
    /**
     * @return list<ServicioDocumentoRequerido>
     */
    public function findByServicioId(ServicioId $servicioId): array;

    /**
     * @param list<ServicioDocumentoRequerido> $documentos
     */
    public function replaceForServicio(ServicioId $servicioId, array $documentos): void;
}
