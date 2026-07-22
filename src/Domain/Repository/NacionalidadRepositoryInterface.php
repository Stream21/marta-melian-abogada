<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Nacionalidad;

interface NacionalidadRepositoryInterface
{
    /**
     * @return list<Nacionalidad>
     */
    public function findAllActivas(): array;

    public function findNombreByCodigo(string $codigo): ?string;
}
