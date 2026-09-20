<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\DestinoRequerimientoMercurio;
use App\Domain\Entity\EstadoRequerimientoMercurio;
use App\Domain\Entity\ExpedienteRequerimientoMercurio;
use App\Domain\Entity\TipoRequerimientoMercurio;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteRequerimientoMercurioOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteRequerimientoMercurioRepository implements ExpedienteRequerimientoMercurioRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExpedienteRequerimientoMercurio $requerimiento): void
    {
        $orm = $this->entityManager->find(
            ExpedienteRequerimientoMercurioOrm::class,
            $requerimiento->id()->value(),
        ) ?? new ExpedienteRequerimientoMercurioOrm();

        $orm->setId($requerimiento->id()->value());
        $orm->setExpedienteId($requerimiento->expedienteId()->value());
        $orm->setTipo($requerimiento->tipo()->value);
        $orm->setDestino($requerimiento->destino()->value);
        $orm->setNombre($requerimiento->nombre());
        $orm->setDescripcion($requerimiento->descripcion());
        $orm->setEstado($requerimiento->estado()->value);
        $orm->setArchivoPath($requerimiento->archivoPath());
        $orm->setArchivoNombre($requerimiento->archivoNombre());
        $orm->setJustificantePresentacionPath($requerimiento->justificantePresentacionPath());
        $orm->setFormularioNombre($requerimiento->formularioNombre());
        $orm->setFormularioCometido($requerimiento->formularioCometido());
        $orm->setOficioPath($requerimiento->oficioPath());
        $orm->setOficioNombre($requerimiento->oficioNombre());
        $orm->setCreatedAt($requerimiento->createdAt());
        $orm->setUpdatedAt($requerimiento->updatedAt());

        $this->entityManager->persist($orm);
        $this->entityManager->flush();
    }

    public function findByExpediente(ExpedienteId $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteRequerimientoMercurioOrm::class)->findBy(
            ['expedienteId' => $expedienteId->value()],
            ['createdAt' => 'DESC'],
        );

        return array_map($this->ormToDomain(...), $orms);
    }

    public function findByExpedienteIds(array $expedienteIds): array
    {
        if ([] === $expedienteIds) {
            return [];
        }

        /** @var ExpedienteRequerimientoMercurioOrm[] $orms */
        $orms = $this->entityManager->getRepository(ExpedienteRequerimientoMercurioOrm::class)
            ->createQueryBuilder('r')
            ->where('r.expedienteId IN (:ids)')
            ->setParameter('ids', $expedienteIds)
            ->orderBy('r.updatedAt', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($expedienteIds as $expedienteId) {
            $result[$expedienteId] = [];
        }
        foreach ($orms as $orm) {
            $result[$orm->getExpedienteId()][] = $this->ormToDomain($orm);
        }

        return $result;
    }

    public function findById(ExpedienteRequerimientoMercurioId $id): ?ExpedienteRequerimientoMercurio
    {
        $orm = $this->entityManager->find(ExpedienteRequerimientoMercurioOrm::class, $id->value());

        return $orm instanceof ExpedienteRequerimientoMercurioOrm ? $this->ormToDomain($orm) : null;
    }

    public function countAbiertosByExpediente(ExpedienteId $expedienteId): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(ExpedienteRequerimientoMercurioOrm::class, 'r')
            ->where('r.expedienteId = :expedienteId')
            ->andWhere('r.estado IN (:estados)')
            ->setParameter('expedienteId', $expedienteId->value())
            ->setParameter('estados', [
                EstadoRequerimientoMercurio::PendienteCliente->value,
                EstadoRequerimientoMercurio::PendienteDespacho->value,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function ormToDomain(ExpedienteRequerimientoMercurioOrm $orm): ExpedienteRequerimientoMercurio
    {
        return new ExpedienteRequerimientoMercurio(
            new ExpedienteRequerimientoMercurioId($orm->getId()),
            new ExpedienteId($orm->getExpedienteId()),
            TipoRequerimientoMercurio::fromString($orm->getTipo()),
            DestinoRequerimientoMercurio::from($orm->getDestino()),
            $orm->getNombre(),
            $orm->getDescripcion(),
            EstadoRequerimientoMercurio::from($orm->getEstado()),
            $orm->getArchivoPath(),
            $orm->getArchivoNombre(),
            $orm->getJustificantePresentacionPath(),
            $orm->getFormularioNombre(),
            $orm->getFormularioCometido(),
            $orm->getOficioPath(),
            $orm->getOficioNombre(),
            $orm->getCreatedAt(),
            $orm->getUpdatedAt(),
        );
    }
}
