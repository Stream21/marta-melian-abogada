<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ExpedientePresentacionTelematica;
use App\Domain\Repository\ExpedientePresentacionTelematicaRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedientePresentacionTelematicaId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedientePresentacionTelematicaOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedientePresentacionTelematicaRepository implements ExpedientePresentacionTelematicaRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExpedientePresentacionTelematica $presentacion): void
    {
        $orm = $this->entityManager->find(
            ExpedientePresentacionTelematicaOrm::class,
            $presentacion->id()->value(),
        ) ?? new ExpedientePresentacionTelematicaOrm();

        $orm->setId($presentacion->id()->value());
        $orm->setExpedienteId($presentacion->expedienteId()->value());
        $orm->setPresentacionPath($presentacion->presentacionPath());
        $orm->setJustificantePath($presentacion->justificantePath());
        $orm->setIdentificadorSolicitud($presentacion->identificadorSolicitud());
        $orm->setFechaPresentacion($presentacion->fechaPresentacion());
        $orm->setNumeroExpedienteExtranjeria($presentacion->numeroExpedienteExtranjeria());
        $orm->setCreatedAt($presentacion->createdAt());
        $orm->setUpdatedAt($presentacion->updatedAt());

        $this->entityManager->persist($orm);
        $this->entityManager->flush();
    }

    public function findByExpediente(ExpedienteId $expedienteId): ?ExpedientePresentacionTelematica
    {
        $orm = $this->entityManager->getRepository(ExpedientePresentacionTelematicaOrm::class)->findOneBy([
            'expedienteId' => $expedienteId->value(),
        ]);

        return $orm instanceof ExpedientePresentacionTelematicaOrm ? $this->ormToDomain($orm) : null;
    }

    public function findById(ExpedientePresentacionTelematicaId $id): ?ExpedientePresentacionTelematica
    {
        $orm = $this->entityManager->find(ExpedientePresentacionTelematicaOrm::class, $id->value());

        return $orm instanceof ExpedientePresentacionTelematicaOrm ? $this->ormToDomain($orm) : null;
    }

    private function ormToDomain(ExpedientePresentacionTelematicaOrm $orm): ExpedientePresentacionTelematica
    {
        return new ExpedientePresentacionTelematica(
            new ExpedientePresentacionTelematicaId($orm->getId()),
            new ExpedienteId($orm->getExpedienteId()),
            $orm->getPresentacionPath(),
            $orm->getJustificantePath(),
            $orm->getIdentificadorSolicitud(),
            $orm->getFechaPresentacion(),
            $orm->getNumeroExpedienteExtranjeria(),
            $orm->getCreatedAt(),
            $orm->getUpdatedAt(),
        );
    }
}
