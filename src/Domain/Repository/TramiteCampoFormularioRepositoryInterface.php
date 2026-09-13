<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\TramiteCampoFormulario;
use App\Domain\ValueObject\TramiteId;

interface TramiteCampoFormularioRepositoryInterface
{
    /**
     * @return list<TramiteCampoFormulario>
     */
    public function findByTramiteId(TramiteId $tramiteId): array;

    /**
     * @param list<TramiteCampoFormulario> $campos
     */
    public function replaceForTramite(TramiteId $tramiteId, array $campos): void;
}
