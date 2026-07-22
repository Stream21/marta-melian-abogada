<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Nacionalidad;
use App\Domain\Repository\NacionalidadRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\NacionalidadOrm;
use Doctrine\ORM\EntityManagerInterface;

final class NacionalidadRepository implements NacionalidadRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findAllActivas(): array
    {
        /** @var list<NacionalidadOrm> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('n')
            ->from(NacionalidadOrm::class, 'n')
            ->where('n.activo = true')
            ->orderBy('n.nombre', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (NacionalidadOrm $row): Nacionalidad => new Nacionalidad($row->getCodigo(), $row->getNombre()),
            $rows,
        );
    }

    public function findNombreByCodigo(string $codigo): ?string
    {
        $codigo = strtoupper(trim($codigo));
        if ('' === $codigo) {
            return null;
        }

        /** @var NacionalidadOrm|null $row */
        $row = $this->entityManager->createQueryBuilder()
            ->select('n')
            ->from(NacionalidadOrm::class, 'n')
            ->where('n.codigo = :codigo')
            ->andWhere('n.activo = true')
            ->setParameter('codigo', $codigo)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row?->getNombre();
    }
}
