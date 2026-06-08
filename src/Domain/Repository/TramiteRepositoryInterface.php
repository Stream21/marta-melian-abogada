<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Tramite;
use App\Domain\ValueObject\ServicioId;
use App\Domain\ValueObject\TramiteId;

interface TramiteRepositoryInterface
{
    /**
     * @return Tramite[]
     */
    public function findAll(bool $incluirInactivos = false, ?ServicioId $servicioId = null): array;

    public function findById(TramiteId $id): ?Tramite;

    public function findByNombreNormalized(
        ServicioId $servicioId,
        string $normalizedNombre,
        ?TramiteId $excluirId = null,
    ): ?Tramite;

    public function save(Tramite $tramite): void;
}
