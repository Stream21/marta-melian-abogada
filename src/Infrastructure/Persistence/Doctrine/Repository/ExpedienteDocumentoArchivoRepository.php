<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ExpedienteDocumentoArchivo;
use App\Domain\Repository\ExpedienteDocumentoArchivoRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteDocumentoArchivoOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteDocumentoArchivoRepository implements ExpedienteDocumentoArchivoRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function saveMany(array $archivos): void
    {
        foreach ($archivos as $archivo) {
            $orm = new ExpedienteDocumentoArchivoOrm();
            $orm->setId($archivo->id());
            $orm->setEntregadoId($archivo->entregadoId());
            $orm->setArchivoPath($archivo->archivoPath());
            $orm->setNombreOriginal($archivo->nombreOriginal());
            $orm->setOrden($archivo->orden());
            $orm->setCreatedAt($archivo->createdAt());
            $this->entityManager->persist($orm);
        }

        $this->entityManager->flush();
    }

    public function deleteByEntregadoId(string $entregadoId): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(ExpedienteDocumentoArchivoOrm::class, 'a')
            ->where('a.entregadoId = :entregadoId')
            ->setParameter('entregadoId', $entregadoId)
            ->getQuery()
            ->execute();
    }

    public function findByEntregadoId(string $entregadoId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteDocumentoArchivoOrm::class)->findBy(
            ['entregadoId' => $entregadoId],
            ['orden' => 'ASC', 'createdAt' => 'ASC'],
        );

        return array_map($this->ormToDomain(...), $orms);
    }

    public function findByEntregadoIds(array $entregadoIds): array
    {
        if ([] === $entregadoIds) {
            return [];
        }

        $orms = $this->entityManager->getRepository(ExpedienteDocumentoArchivoOrm::class)->findBy(
            ['entregadoId' => $entregadoIds],
            ['orden' => 'ASC', 'createdAt' => 'ASC'],
        );

        $grouped = [];
        foreach ($orms as $orm) {
            $grouped[$orm->getEntregadoId()][] = $this->ormToDomain($orm);
        }

        return $grouped;
    }

    public function findById(string $id): ?ExpedienteDocumentoArchivo
    {
        $orm = $this->entityManager->find(ExpedienteDocumentoArchivoOrm::class, $id);

        return $orm instanceof ExpedienteDocumentoArchivoOrm ? $this->ormToDomain($orm) : null;
    }

    private function ormToDomain(ExpedienteDocumentoArchivoOrm $orm): ExpedienteDocumentoArchivo
    {
        return new ExpedienteDocumentoArchivo(
            $orm->getId(),
            $orm->getEntregadoId(),
            $orm->getArchivoPath(),
            $orm->getNombreOriginal(),
            $orm->getOrden(),
            $orm->getCreatedAt(),
        );
    }
}
