<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteDocumentoEntregadoOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteDocumentoRepository implements ExpedienteDocumentoRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExpedienteDocumentoEntregado $documento): void
    {
        $existing = $this->entityManager->getRepository(ExpedienteDocumentoEntregadoOrm::class)->findOneBy([
            'expedienteId' => $documento->expedienteId()->value(),
            'documentoRequeridoId' => $documento->documentoRequeridoId()->value(),
        ]);

        if ($existing instanceof ExpedienteDocumentoEntregadoOrm) {
            $existing->setArchivoPath($documento->archivoPath());
            $existing->setEstado($documento->estado()->value);
            $existing->setEntregadoAt($documento->entregadoAt());
        } else {
            $orm = new ExpedienteDocumentoEntregadoOrm();
            $orm->setId($documento->id());
            $orm->setExpedienteId($documento->expedienteId()->value());
            $orm->setDocumentoRequeridoId($documento->documentoRequeridoId()->value());
            $orm->setArchivoPath($documento->archivoPath());
            $orm->setEstado($documento->estado()->value);
            $orm->setEntregadoAt($documento->entregadoAt());
            $this->entityManager->persist($orm);
        }

        $this->entityManager->flush();
    }

    public function findByExpediente(ExpedienteId $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteDocumentoEntregadoOrm::class)->findBy([
            'expedienteId' => $expedienteId->value(),
        ]);

        return array_map($this->ormToDomain(...), $orms);
    }

    public function findByExpedienteAndDocumento(
        ExpedienteId $expedienteId,
        TramiteDocumentoRequeridoId $documentoRequeridoId,
    ): ?ExpedienteDocumentoEntregado {
        $orm = $this->entityManager->getRepository(ExpedienteDocumentoEntregadoOrm::class)->findOneBy([
            'expedienteId' => $expedienteId->value(),
            'documentoRequeridoId' => $documentoRequeridoId->value(),
        ]);

        return $orm instanceof ExpedienteDocumentoEntregadoOrm ? $this->ormToDomain($orm) : null;
    }

    private function ormToDomain(ExpedienteDocumentoEntregadoOrm $orm): ExpedienteDocumentoEntregado
    {
        return new ExpedienteDocumentoEntregado(
            $orm->getId(),
            new ExpedienteId($orm->getExpedienteId()),
            new TramiteDocumentoRequeridoId($orm->getDocumentoRequeridoId()),
            $orm->getArchivoPath(),
            EstadoDocumentoEntregado::from($orm->getEstado()),
            $orm->getEntregadoAt(),
        );
    }
}
