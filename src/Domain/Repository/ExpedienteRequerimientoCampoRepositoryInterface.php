<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ExpedienteRequerimientoCampo;
use App\Domain\ValueObject\ExpedienteRequerimientoCampoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

interface ExpedienteRequerimientoCampoRepositoryInterface
{
    public function save(ExpedienteRequerimientoCampo $campo): void;

    /**
     * @param list<ExpedienteRequerimientoCampo> $campos
     */
    public function saveAll(array $campos): void;

    public function findById(ExpedienteRequerimientoCampoId $id): ?ExpedienteRequerimientoCampo;

    /**
     * @return list<ExpedienteRequerimientoCampo>
     */
    public function findByRequerimientoId(ExpedienteRequerimientoMercurioId $requerimientoId): array;

    public function delete(ExpedienteRequerimientoCampoId $id): void;

    public function deleteByRequerimientoId(ExpedienteRequerimientoMercurioId $id): void;
}
