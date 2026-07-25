<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ExpedienteRecordatorioFuturo;
use App\Domain\Repository\ExpedienteRecordatorioFuturoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRecordatorioFuturoId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteRecordatorioFuturoOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteRecordatorioFuturoRepository implements ExpedienteRecordatorioFuturoRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExpedienteRecordatorioFuturo $recordatorio): void
    {
        $orm = $this->entityManager->find(
            ExpedienteRecordatorioFuturoOrm::class,
            $recordatorio->id()->value(),
        ) ?? new ExpedienteRecordatorioFuturoOrm();

        $orm->setId($recordatorio->id()->value());
        $orm->setExpedienteId($recordatorio->expedienteId()->value());
        $orm->setFecha($recordatorio->fecha());
        $orm->setMotivo($recordatorio->motivo());
        $orm->setServicioId($recordatorio->servicioId());
        $orm->setTramiteId($recordatorio->tramiteId());
        $orm->setServicioNombre($recordatorio->servicioNombre());
        $orm->setTramiteNombre($recordatorio->tramiteNombre());
        $orm->setNotificadoAt($recordatorio->notificadoAt());
        $orm->setCreatedAt($recordatorio->createdAt());

        $this->entityManager->persist($orm);
        $this->entityManager->flush();
    }

    public function findByExpediente(ExpedienteId $expedienteId): ?ExpedienteRecordatorioFuturo
    {
        $orm = $this->entityManager->getRepository(ExpedienteRecordatorioFuturoOrm::class)->findOneBy([
            'expedienteId' => $expedienteId->value(),
        ]);

        return $orm instanceof ExpedienteRecordatorioFuturoOrm ? $this->ormToDomain($orm) : null;
    }

    public function findById(ExpedienteRecordatorioFuturoId $id): ?ExpedienteRecordatorioFuturo
    {
        $orm = $this->entityManager->find(ExpedienteRecordatorioFuturoOrm::class, $id->value());

        return $orm instanceof ExpedienteRecordatorioFuturoOrm ? $this->ormToDomain($orm) : null;
    }

    public function findPendientesHasta(\DateTimeImmutable $hasta): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('r')
            ->from(ExpedienteRecordatorioFuturoOrm::class, 'r')
            ->where('r.notificadoAt IS NULL')
            ->andWhere('r.fecha <= :hasta')
            ->setParameter('hasta', $hasta)
            ->orderBy('r.fecha', 'ASC');

        $result = [];
        foreach ($qb->getQuery()->getResult() as $orm) {
            if ($orm instanceof ExpedienteRecordatorioFuturoOrm) {
                $result[] = $this->ormToDomain($orm);
            }
        }

        return $result;
    }

    private function ormToDomain(ExpedienteRecordatorioFuturoOrm $orm): ExpedienteRecordatorioFuturo
    {
        return new ExpedienteRecordatorioFuturo(
            new ExpedienteRecordatorioFuturoId($orm->getId()),
            new ExpedienteId($orm->getExpedienteId()),
            $orm->getFecha(),
            $orm->getMotivo(),
            $orm->getServicioId(),
            $orm->getTramiteId(),
            $orm->getServicioNombre(),
            $orm->getTramiteNombre(),
            $orm->getNotificadoAt(),
            $orm->getCreatedAt(),
        );
    }
}
