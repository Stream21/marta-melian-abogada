<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ActorResponsableDocumento;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\SubidoPorDocumento;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
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
        $existing = null;

        if (null !== $documento->expedienteDocumentoRequeridoId()) {
            $existing = $this->entityManager->getRepository(ExpedienteDocumentoEntregadoOrm::class)->findOneBy([
                'expedienteId' => $documento->expedienteId()->value(),
                'expedienteDocumentoRequeridoId' => $documento->expedienteDocumentoRequeridoId()->value(),
            ]);
        } elseif (null !== $documento->documentoRequeridoId()) {
            $existing = $this->entityManager->getRepository(ExpedienteDocumentoEntregadoOrm::class)->findOneBy([
                'expedienteId' => $documento->expedienteId()->value(),
                'documentoRequeridoId' => $documento->documentoRequeridoId()->value(),
            ]);
        }

        if ($existing instanceof ExpedienteDocumentoEntregadoOrm) {
            $existing->setArchivoPath($documento->archivoPath());
            $existing->setEstado($documento->estado()->value);
            $existing->setEntregadoAt($documento->entregadoAt());
            $existing->setNotaRechazo($documento->notaRechazo());
            $existing->setSubidoPor($documento->subidoPor()->value);
            $existing->setResponsableActual($documento->responsableActual()->value);
        } else {
            $orm = new ExpedienteDocumentoEntregadoOrm();
            $orm->setId($documento->id());
            $orm->setExpedienteId($documento->expedienteId()->value());
            $orm->setDocumentoRequeridoId($documento->documentoRequeridoId()?->value());
            $orm->setExpedienteDocumentoRequeridoId($documento->expedienteDocumentoRequeridoId()?->value());
            $orm->setArchivoPath($documento->archivoPath());
            $orm->setEstado($documento->estado()->value);
            $orm->setEntregadoAt($documento->entregadoAt());
            $orm->setNotaRechazo($documento->notaRechazo());
            $orm->setSubidoPor($documento->subidoPor()->value);
            $orm->setResponsableActual($documento->responsableActual()->value);
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

    public function countPendientesRevisionByExpedienteIds(array $expedienteIds): array
    {
        if ([] === $expedienteIds) {
            return [];
        }

        $rows = $this->entityManager->getRepository(ExpedienteDocumentoEntregadoOrm::class)
            ->createQueryBuilder('d')
            ->select('d.expedienteId AS expedienteId, COUNT(d.id) AS cnt')
            ->where('d.expedienteId IN (:ids)')
            ->andWhere('d.expedienteDocumentoRequeridoId IS NOT NULL')
            ->andWhere('d.estado = :estado')
            ->andWhere('d.subidoPor = :subidoPor')
            ->setParameter('ids', $expedienteIds)
            ->setParameter('estado', EstadoDocumentoEntregado::Entregado->value)
            ->setParameter('subidoPor', SubidoPorDocumento::Cliente->value)
            ->groupBy('d.expedienteId')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['expedienteId']] = (int) $row['cnt'];
        }

        return $result;
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

    public function findByExpedienteAndExpedienteDocumento(
        ExpedienteId $expedienteId,
        ExpedienteDocumentoRequeridoId $expedienteDocumentoRequeridoId,
    ): ?ExpedienteDocumentoEntregado {
        $orm = $this->entityManager->getRepository(ExpedienteDocumentoEntregadoOrm::class)->findOneBy([
            'expedienteId' => $expedienteId->value(),
            'expedienteDocumentoRequeridoId' => $expedienteDocumentoRequeridoId->value(),
        ]);

        return $orm instanceof ExpedienteDocumentoEntregadoOrm ? $this->ormToDomain($orm) : null;
    }

    public function deleteById(string $id): void
    {
        $orm = $this->entityManager->find(ExpedienteDocumentoEntregadoOrm::class, $id);
        if (!$orm instanceof ExpedienteDocumentoEntregadoOrm) {
            return;
        }

        $this->entityManager->remove($orm);
        $this->entityManager->flush();
    }

    private function ormToDomain(ExpedienteDocumentoEntregadoOrm $orm): ExpedienteDocumentoEntregado
    {
        return new ExpedienteDocumentoEntregado(
            $orm->getId(),
            new ExpedienteId($orm->getExpedienteId()),
            null !== $orm->getDocumentoRequeridoId()
                ? new TramiteDocumentoRequeridoId($orm->getDocumentoRequeridoId())
                : null,
            null !== $orm->getExpedienteDocumentoRequeridoId()
                ? new ExpedienteDocumentoRequeridoId($orm->getExpedienteDocumentoRequeridoId())
                : null,
            $orm->getArchivoPath(),
            EstadoDocumentoEntregado::from($orm->getEstado()),
            $orm->getEntregadoAt(),
            $orm->getNotaRechazo(),
            SubidoPorDocumento::from($orm->getSubidoPor()),
            ActorResponsableDocumento::fromString($orm->getResponsableActual()),
        );
    }
}
