<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\HojaEncargoPlantilla;
use App\Domain\ValueObject\TramiteId;

interface HojaEncargoPlantillaRepositoryInterface
{
    public function findByTramiteId(TramiteId $tramiteId): ?HojaEncargoPlantilla;

    public function save(HojaEncargoPlantilla $plantilla): void;
}
