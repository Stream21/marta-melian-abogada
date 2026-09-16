<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ExpedienteNota;
use App\Domain\Repository\ExpedienteNotaRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteNotaId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteNotaOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteNotaRepository implements ExpedienteNotaRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExpedienteNota $nota): void
    {
        $orm = $this->entityManager->find(ExpedienteNotaOrm::class, $nota->id()->value())
            ?? new ExpedienteNotaOrm();

        $orm->setId($nota->id()->value());
        $orm->setExpedienteId($nota->expedienteId()->value());
        $orm->setContenido($nota->contenido());
        $orm->setArchivada($nota->archivada());
        $orm->setArchivadaAt($nota->archivadaAt());
        $orm->setCreatedAt($nota->createdAt());

        $this->entityManager->persist($orm);
        $this->entityManager->flush();
    }

    public function findById(ExpedienteNotaId $id): ?ExpedienteNota
    {
        $orm = $this->entityManager->find(ExpedienteNotaOrm::class, $id->value());

        return $orm instanceof ExpedienteNotaOrm ? $this->ormToDomain($orm) : null;
    }

    public function findByExpediente(ExpedienteId $expedienteId): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('n')
            ->from(ExpedienteNotaOrm::class, 'n')
            ->where('n.expedienteId = :expedienteId')
            ->setParameter('expedienteId', $expedienteId->value())
            ->orderBy('n.createdAt', 'DESC');

        $result = [];
        foreach ($qb->getQuery()->getResult() as $orm) {
            if ($orm instanceof ExpedienteNotaOrm) {
                $result[] = $this->ormToDomain($orm);
            }
        }

        return $result;
    }

    public function resumenPorExpedientes(array $expedienteIds): array
    {
        $result = [];
        foreach ($expedienteIds as $id) {
            $result[$id] = ['activas' => 0, 'ultima' => null];
        }

        if ([] === $expedienteIds) {
            return $result;
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('n')
            ->from(ExpedienteNotaOrm::class, 'n')
            ->where('n.expedienteId IN (:ids)')
            ->setParameter('ids', $expedienteIds)
            ->orderBy('n.createdAt', 'DESC');

        /** @var ExpedienteNotaOrm[] $rows */
        $rows = $qb->getQuery()->getResult();

        foreach ($rows as $orm) {
            if (!$orm instanceof ExpedienteNotaOrm) {
                continue;
            }
            $expId = $orm->getExpedienteId();
            if (!isset($result[$expId])) {
                continue;
            }

            if (!$orm->isArchivada()) {
                ++$result[$expId]['activas'];
                // Primera activa por expediente = más reciente activa (ORDER BY created_at DESC).
                if (null === $result[$expId]['ultima']) {
                    $result[$expId]['ultima'] = $this->ormToDomain($orm);
                }
            }
        }

        return $result;
    }

    private function ormToDomain(ExpedienteNotaOrm $orm): ExpedienteNota
    {
        return new ExpedienteNota(
            new ExpedienteNotaId($orm->getId()),
            new ExpedienteId($orm->getExpedienteId()),
            $orm->getContenido(),
            $orm->isArchivada(),
            $orm->getArchivadaAt(),
            $orm->getCreatedAt(),
        );
    }
}
