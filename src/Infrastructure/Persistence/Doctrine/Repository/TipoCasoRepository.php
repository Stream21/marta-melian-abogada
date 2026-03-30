<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\TipoCaso;
use App\Domain\Repository\TipoCasoRepositoryInterface;
use App\Domain\ValueObject\TipoCasoId;
use App\Infrastructure\Persistence\Doctrine\Entity\TipoCasoOrm;
use Doctrine\ORM\EntityManagerInterface;

final class TipoCasoRepository implements TipoCasoRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findAll(): array
    {
        $orms = $this->entityManager->getRepository(TipoCasoOrm::class)->findBy([], ['nombre' => 'ASC']);

        return array_map($this->ormToDomain(...), $orms);
    }

    public function findById(TipoCasoId $id): ?TipoCaso
    {
        $orm = $this->entityManager->getRepository(TipoCasoOrm::class)->find($id->value());

        return $orm instanceof TipoCasoOrm ? $this->ormToDomain($orm) : null;
    }

    public function findByNombreNormalized(string $normalizedNombre, ?TipoCasoId $excluirId = null): ?TipoCaso
    {
        $qb = $this->entityManager
            ->getRepository(TipoCasoOrm::class)
            ->createQueryBuilder('t')
            ->where('LOWER(TRIM(t.nombre)) = :n')
            ->setParameter('n', $normalizedNombre)
            ->setMaxResults(1);

        if ($excluirId instanceof TipoCasoId) {
            $qb->andWhere('t.id <> :excluir')->setParameter('excluir', $excluirId->value());
        }

        $orm = $qb->getQuery()->getOneOrNullResult();

        return $orm instanceof TipoCasoOrm ? $this->ormToDomain($orm) : null;
    }

    public function save(TipoCaso $tipoCaso): void
    {
        $existing = $this->entityManager->getRepository(TipoCasoOrm::class)->find($tipoCaso->id()->value());

        if ($existing instanceof TipoCasoOrm) {
            $existing->setNombre($tipoCaso->nombre());
            $existing->setDescripcion($tipoCaso->descripcion());
        } else {
            $this->entityManager->persist($this->domainToOrm($tipoCaso));
        }

        $this->entityManager->flush();
    }

    public function remove(TipoCaso $tipoCaso): void
    {
        $orm = $this->entityManager->getRepository(TipoCasoOrm::class)->find($tipoCaso->id()->value());
        if ($orm instanceof TipoCasoOrm) {
            $this->entityManager->remove($orm);
            $this->entityManager->flush();
        }
    }

    private function ormToDomain(TipoCasoOrm $orm): TipoCaso
    {
        return new TipoCaso(
            new TipoCasoId($orm->getId()),
            $orm->getNombre(),
            $orm->getDescripcion(),
        );
    }

    private function domainToOrm(TipoCaso $tipoCaso): TipoCasoOrm
    {
        $orm = new TipoCasoOrm();
        $orm->setId($tipoCaso->id()->value());
        $orm->setNombre($tipoCaso->nombre());
        $orm->setDescripcion($tipoCaso->descripcion());

        return $orm;
    }
}
