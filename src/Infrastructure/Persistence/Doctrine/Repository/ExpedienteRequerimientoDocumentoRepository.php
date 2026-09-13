<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\EstadoRequerimientoDocumento;
use App\Domain\Entity\ExpedienteRequerimientoDocumento;
use App\Domain\Entity\ResponsableRequerimientoDocumento;
use App\Domain\Repository\ExpedienteRequerimientoDocumentoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteRequerimientoDocumentoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteRequerimientoDocumentoOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteRequerimientoDocumentoRepository implements ExpedienteRequerimientoDocumentoRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExpedienteRequerimientoDocumento $documento): void
    {
        $this->persist($documento);
        $this->entityManager->flush();
    }

    public function saveAll(array $documentos): void
    {
        foreach ($documentos as $documento) {
            $this->persist($documento);
        }
        $this->entityManager->flush();
    }

    public function findById(ExpedienteRequerimientoDocumentoId $id): ?ExpedienteRequerimientoDocumento
    {
        $orm = $this->entityManager->find(ExpedienteRequerimientoDocumentoOrm::class, $id->value());

        return $orm instanceof ExpedienteRequerimientoDocumentoOrm ? $this->ormToDomain($orm) : null;
    }

    public function findByRequerimientoId(ExpedienteRequerimientoMercurioId $requerimientoId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteRequerimientoDocumentoOrm::class)->findBy(
            ['requerimientoId' => $requerimientoId->value()],
            ['orden' => 'ASC', 'nombre' => 'ASC'],
        );

        return array_map($this->ormToDomain(...), $orms);
    }

    public function delete(ExpedienteRequerimientoDocumentoId $id): void
    {
        $orm = $this->entityManager->find(ExpedienteRequerimientoDocumentoOrm::class, $id->value());
        if (!$orm instanceof ExpedienteRequerimientoDocumentoOrm) {
            return;
        }

        $this->entityManager->remove($orm);
        $this->entityManager->flush();
    }

    private function persist(ExpedienteRequerimientoDocumento $documento): void
    {
        $orm = $this->entityManager->find(ExpedienteRequerimientoDocumentoOrm::class, $documento->id()->value())
            ?? new ExpedienteRequerimientoDocumentoOrm();

        $now = new \DateTimeImmutable();
        if (!$this->entityManager->contains($orm)) {
            $orm->setId($documento->id()->value());
            $orm->setCreatedAt($now);
            $this->entityManager->persist($orm);
        }

        $orm->setRequerimientoId($documento->requerimientoId()->value());
        $orm->setNombre($documento->nombre());
        $orm->setDescripcion($documento->descripcion());
        $orm->setResponsable($documento->responsable()->value);
        $orm->setObligatorio($documento->obligatorio());
        $orm->setEstado($documento->estado()->value);
        $orm->setArchivoPath($documento->archivoPath());
        $orm->setNotaRechazo($documento->notaRechazo());
        $orm->setOrden($documento->orden());
        $orm->setMaxArchivos($documento->maxArchivos());
        $orm->setUpdatedAt($now);
    }

    private function ormToDomain(ExpedienteRequerimientoDocumentoOrm $orm): ExpedienteRequerimientoDocumento
    {
        return new ExpedienteRequerimientoDocumento(
            new ExpedienteRequerimientoDocumentoId($orm->getId()),
            new ExpedienteRequerimientoMercurioId($orm->getRequerimientoId()),
            $orm->getNombre(),
            $orm->getDescripcion(),
            ResponsableRequerimientoDocumento::from($orm->getResponsable()),
            $orm->isObligatorio(),
            EstadoRequerimientoDocumento::from($orm->getEstado()),
            $orm->getOrden(),
            $orm->getArchivoPath(),
            $orm->getNotaRechazo(),
            max(1, $orm->getMaxArchivos()),
        );
    }
}
