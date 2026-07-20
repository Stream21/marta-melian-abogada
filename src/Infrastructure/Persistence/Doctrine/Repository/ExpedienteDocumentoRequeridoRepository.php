<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\OrigenDocumentoRequeridoExpediente;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ServicioDocumentoRequeridoId;
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
        $orm = $this->entityManager->find(ExpedienteDocumentoRequeridoOrm::class, $documento->id()->value())
            ?? new ExpedienteDocumentoRequeridoOrm();

        $orm->setId($documento->id()->value());
        $orm->setExpedienteId($documento->expedienteId()->value());
        $orm->setNombre($documento->nombre());
        $orm->setDescripcion($documento->descripcion());
        $orm->setObligatorio($documento->obligatorio());
        $orm->setTipo($documento->tipo()->value);
        $orm->setMaxImagenes($documento->maxImagenes());
        $orm->setOrden($documento->orden());
        $orm->setOrigen($documento->origen()->value);
        $orm->setTramiteDocumentoRequeridoId($documento->tramiteDocumentoRequeridoId()?->value());
        $orm->setServicioDocumentoRequeridoId($documento->servicioDocumentoRequeridoId()?->value());
        $orm->setCreatedAt($documento->createdAt());

        $this->entityManager->persist($orm);
        $this->entityManager->flush();
    }

    public function findByExpediente(ExpedienteId $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteDocumentoRequeridoOrm::class)->findBy(
            ['expedienteId' => $expedienteId->value()],
            ['orden' => 'ASC', 'nombre' => 'ASC'],
        );

        return array_map($this->ormToDomain(...), $orms);
    }

    public function findById(ExpedienteDocumentoRequeridoId $id): ?ExpedienteDocumentoRequerido
    {
        $orm = $this->entityManager->find(ExpedienteDocumentoRequeridoOrm::class, $id->value());

        return $orm instanceof ExpedienteDocumentoRequeridoOrm ? $this->ormToDomain($orm) : null;
    }

    public function countByExpediente(ExpedienteId $expedienteId): int
    {
        return (int) $this->entityManager->getRepository(ExpedienteDocumentoRequeridoOrm::class)->count([
            'expedienteId' => $expedienteId->value(),
        ]);
    }

    public function delete(ExpedienteDocumentoRequeridoId $id): void
    {
        $orm = $this->entityManager->find(ExpedienteDocumentoRequeridoOrm::class, $id->value());
        if (!$orm instanceof ExpedienteDocumentoRequeridoOrm) {
            return;
        }

        $this->entityManager->remove($orm);
        $this->entityManager->flush();
    }

    private function ormToDomain(ExpedienteDocumentoRequeridoOrm $orm): ExpedienteDocumentoRequerido
    {
        return new ExpedienteDocumentoRequerido(
            new ExpedienteDocumentoRequeridoId($orm->getId()),
            new ExpedienteId($orm->getExpedienteId()),
            $orm->getNombre(),
            $orm->getDescripcion(),
            $orm->isObligatorio(),
            TipoDocumentoRequerido::fromString($orm->getTipo()),
            $orm->getMaxImagenes(),
            $orm->getOrden(),
            OrigenDocumentoRequeridoExpediente::from($orm->getOrigen()),
            null !== $orm->getTramiteDocumentoRequeridoId()
                ? new TramiteDocumentoRequeridoId($orm->getTramiteDocumentoRequeridoId())
                : null,
            null !== $orm->getServicioDocumentoRequeridoId()
                ? new ServicioDocumentoRequeridoId($orm->getServicioDocumentoRequeridoId())
                : null,
            $orm->getCreatedAt(),
        );
    }
}
