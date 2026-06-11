<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\EscritoPlantilla;
use App\Domain\Entity\TipoEscrito;
use App\Domain\ValueObject\TramiteId;

interface EscritoPlantillaRepositoryInterface
{
    public function save(EscritoPlantilla $plantilla): void;

    public function findByTramiteAndTipo(TramiteId $tramiteId, TipoEscrito $tipo): ?EscritoPlantilla;
}
