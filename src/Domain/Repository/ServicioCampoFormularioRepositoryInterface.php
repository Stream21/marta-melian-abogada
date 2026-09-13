<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ServicioCampoFormulario;
use App\Domain\ValueObject\ServicioId;

interface ServicioCampoFormularioRepositoryInterface
{
    /**
     * @return list<ServicioCampoFormulario>
     */
    public function findByServicioId(ServicioId $servicioId): array;

    /**
     * @param list<ServicioCampoFormulario> $campos
     */
    public function replaceForServicio(ServicioId $servicioId, array $campos): void;
}
