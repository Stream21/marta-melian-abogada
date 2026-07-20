<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ExpedienteEscrito;
use App\Domain\Repository\ExpedienteEscritoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteEscritoOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteEscritoRepository implements ExpedienteEscritoRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExpedienteEscrito $escrito): void
    {
        $orm = $this->entityManager->find(ExpedienteEscritoOrm::class, $escrito->id())
            ?? new ExpedienteEscritoOrm();

        $orm->setId($escrito->id());
        $orm->setExpedienteId($escrito->expedienteId()->value());
        $orm->setTitulo($escrito->titulo());
        $orm->setContenidoHtml($escrito->contenidoHtml());
        $orm->setPdfPath($escrito->pdfPath());
        $orm->setCreatedAt($escrito->createdAt());

        $this->entityManager->persist($orm);
        $this->entityManager->flush();
    }

    public function findByExpediente(ExpedienteId $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteEscritoOrm::class)->findBy(
            ['expedienteId' => $expedienteId->value()],
            ['createdAt' => 'DESC'],
        );

        return array_map($this->ormToDomain(...), $orms);
    }

    public function findById(string $id): ?ExpedienteEscrito
    {
        $orm = $this->entityManager->find(ExpedienteEscritoOrm::class, $id);

        return $orm instanceof ExpedienteEscritoOrm ? $this->ormToDomain($orm) : null;
    }

    private function ormToDomain(ExpedienteEscritoOrm $orm): ExpedienteEscrito
    {
        return new ExpedienteEscrito(
            $orm->getId(),
            new ExpedienteId($orm->getExpedienteId()),
            $orm->getTitulo(),
            $orm->getContenidoHtml(),
            $orm->getPdfPath(),
            $orm->getCreatedAt(),
        );
    }
}
