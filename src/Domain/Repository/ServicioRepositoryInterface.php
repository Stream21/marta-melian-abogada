<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Servicio;
use App\Domain\ValueObject\ServicioId;

interface ServicioRepositoryInterface
{
    /**
     * @return Servicio[]
     */
    public function findAll(bool $incluirInactivos = false): array;

    public function findById(ServicioId $id): ?Servicio;

    public function findByNombreNormalized(string $normalizedNombre, ?ServicioId $excluirId = null): ?Servicio;

    public function save(Servicio $servicio): void;
}
