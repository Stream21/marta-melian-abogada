<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\TipoCaso;
use App\Domain\ValueObject\TipoCasoId;

interface TipoCasoRepositoryInterface
{
    /**
     * @return TipoCaso[]
     */
    public function findAll(): array;

    public function findById(TipoCasoId $id): ?TipoCaso;

    /**
     * Busca por nombre normalizado (trim + minúsculas Unicode) para unicidad lógica.
     * Si se indica $excluirId, se ignora ese tipo (útil al editar).
     */
    public function findByNombreNormalized(string $normalizedNombre, ?TipoCasoId $excluirId = null): ?TipoCaso;

    public function save(TipoCaso $tipoCaso): void;

    public function remove(TipoCaso $tipoCaso): void;
}
