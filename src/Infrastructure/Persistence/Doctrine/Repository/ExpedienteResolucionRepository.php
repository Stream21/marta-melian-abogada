<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ExpedienteResolucion;
use App\Domain\Entity\GestionPostResolucion;
use App\Domain\Entity\OutcomeResolucion;
use App\Domain\Repository\ExpedienteResolucionRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteResolucionId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteResolucionOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteResolucionRepository implements ExpedienteResolucionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExpedienteResolucion $resolucion): void
    {
        $orm = $this->entityManager->find(
            ExpedienteResolucionOrm::class,
            $resolucion->id()->value(),
        ) ?? new ExpedienteResolucionOrm();

        $orm->setId($resolucion->id()->value());
        $orm->setExpedienteId($resolucion->expedienteId()->value());
        $orm->setOutcome($resolucion->outcome()->value);
        $orm->setResolucionPath($resolucion->resolucionPath());
        $orm->setFechaNotificacion($resolucion->fechaNotificacion());
        $orm->setGestionesJson(json_encode(
            array_map(static fn (GestionPostResolucion $g) => $g->toArray(), $resolucion->gestiones()),
            JSON_THROW_ON_ERROR,
        ));
        $orm->setCreatedAt($resolucion->createdAt());
        $orm->setUpdatedAt($resolucion->updatedAt());

        $this->entityManager->persist($orm);
        $this->entityManager->flush();
    }

    public function findByExpediente(ExpedienteId $expedienteId): ?ExpedienteResolucion
    {
        $orm = $this->entityManager->getRepository(ExpedienteResolucionOrm::class)->findOneBy([
            'expedienteId' => $expedienteId->value(),
        ]);

        return $orm instanceof ExpedienteResolucionOrm ? $this->ormToDomain($orm) : null;
    }

    public function findById(ExpedienteResolucionId $id): ?ExpedienteResolucion
    {
        $orm = $this->entityManager->find(ExpedienteResolucionOrm::class, $id->value());

        return $orm instanceof ExpedienteResolucionOrm ? $this->ormToDomain($orm) : null;
    }

    private function ormToDomain(ExpedienteResolucionOrm $orm): ExpedienteResolucion
    {
        $decoded = json_decode($orm->getGestionesJson(), true);
        $gestiones = [];
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (is_array($item)) {
                    $gestiones[] = GestionPostResolucion::fromArray($item);
                }
            }
        }

        return new ExpedienteResolucion(
            new ExpedienteResolucionId($orm->getId()),
            new ExpedienteId($orm->getExpedienteId()),
            OutcomeResolucion::from($orm->getOutcome()),
            $orm->getResolucionPath(),
            $orm->getFechaNotificacion(),
            $gestiones,
            $orm->getCreatedAt(),
            $orm->getUpdatedAt(),
        );
    }
}
