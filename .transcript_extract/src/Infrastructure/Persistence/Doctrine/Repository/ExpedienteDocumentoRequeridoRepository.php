<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\OrigenDocumentoRequeridoExpediente;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteDocumentoRequeridoOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteDocumentoRequeridoRepository implements ExpedienteDocumentoRequeridoRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExpedienteDocumentoRequerido $documento): void
    {
        $orm = $this->entityManager->find(ExpedienteDocumentoRequeridoOrm::class, $documento->id()->value());

        if (!$orm instanceof ExpedienteDocumentoRequeridoOrm) {
            $orm = new ExpedienteDocumentoRequeridoOrm();
            $orm->setId($documento->id()->value());
            $this->entityManager->persist($orm);
        }

        $orm->setExpedienteId($documento->expedienteId()->value());
        $orm->setNombre($documento->nombre());
        $orm->setDescripcion($documento->descripcion());
        $orm->setObligatorio($documento->obligatorio());
        $orm->setTipo($documento->tipo()->value);
        $orm->setMaxImagenes($documento->maxImagenes());
        $orm->setOrden($documento->orden());
        $orm->setOrigen($documento->origen()->value);
        $orm->setTramiteDocumentoRequeridoId($documento->tramiteDocumentoRequeridoId()?->value());
        $orm->setCreatedAt($documento->createdAt());

        $this->entityManager->flush();
    }

    public function findByExpediente(ExpedienteId $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteDocumentoRequeridoOrm::class)
            ->createQueryBuilder('d')
            ->where('d.expedienteId = :expedienteId')
            ->setParameter('expedienteId', $expedienteId->value())
            ->orderBy('d.orden', 'ASC')
            ->addOrderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map($this->ormToDomain(...), $orms);
    }

    public function findById(ExpedienteDocumentoRequeridoId $id): ?ExpedienteDocumentoRequerido
    {
        $orm = $this->entityManager->find(ExpedienteDocumentoRequeridoOrm::class, $id->value());

        return $orm instanceof ExpedienteDocumentoRequeridoOrm ? $this->ormToDomain($orm) : null;
    }

    public function countByExpediente(ExpedienteId $expedienteId): int
    {
        return (int) $this->entityManager->getRepository(ExpedienteDocumentoRequeridoOrm::class)
            ->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.expedienteId = :expedienteId')
            ->setParameter('expedienteId', $expedienteId->value())
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function ormToDomain(ExpedienteDocumentoRequeridoOrm $orm): ExpedienteDocumentoRequerido
    {
        return new ExpedienteDocumentoRequerido(
            new ExpedienteDocumentoRequeridoId($orm->getId()),
            new ExpedienteId($orm->getExpedienteId()),
            $orm->getNombre(),
            $orm->getDescripcion(),
            $orm->isObligatorio(),
            TipoDocumentoRequerido::from($orm->getTipo()),
            $orm->getMaxImagenes(),
            $orm->getOrden(),
            OrigenDocumentoRequeridoExpediente::from($orm->getOrigen()),
            null !== $orm->getTramiteDocumentoRequeridoId()
                ? new TramiteDocumentoRequeridoId($orm->getTramiteDocumentoRequeridoId())
                : null,
            $orm->getCreatedAt(),
        );
    }
}
