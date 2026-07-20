<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ContratacionPaso;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Infrastructure\Persistence\Doctrine\Entity\ContratacionPasoOrm;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteHitoOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ContratacionRepository implements ContratacionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function savePaso(ContratacionPaso $paso): void
    {
        $existing = $this->entityManager->getRepository(ContratacionPasoOrm::class)->findOneBy([
            'expedienteId' => $paso->expedienteId()->value(),
            'paso' => $paso->paso()->value,
        ]);

        if ($existing instanceof ContratacionPasoOrm) {
            $existing->setEstado($paso->estado()->value);
            $existing->setRealizadoAt($paso->realizadoAt());
            $existing->setValidadoAt($paso->validadoAt());
            $existing->setNotaDevolucion($paso->notaDevolucion());
            $existing->setMotivosDevolucion($paso->motivosDevolucion() ?: null);
        } else {
            $orm = new ContratacionPasoOrm();
            $orm->setId($paso->id());
            $orm->setExpedienteId($paso->expedienteId()->value());
            $orm->setPaso($paso->paso()->value);
            $orm->setEstado($paso->estado()->value);
            $orm->setRealizadoAt($paso->realizadoAt());
            $orm->setValidadoAt($paso->validadoAt());
            $orm->setNotaDevolucion($paso->notaDevolucion());
            $orm->setMotivosDevolucion($paso->motivosDevolucion() ?: null);
            $this->entityManager->persist($orm);
        }

        $this->entityManager->flush();
    }

    public function findPasosByExpediente(ExpedienteId $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(ContratacionPasoOrm::class)->findBy(
            ['expedienteId' => $expedienteId->value()],
        );

        $pasos = [];
        foreach ($orms as $orm) {
            $paso = $this->pasoOrmToDomain($orm);
            if (null !== $paso) {
                $pasos[] = $paso;
            }
        }

        return $pasos;
    }

    public function findPasosByExpedienteIds(array $expedienteIds): array
    {
        if ([] === $expedienteIds) {
            return [];
        }

        $orms = $this->entityManager->getRepository(ContratacionPasoOrm::class)
            ->createQueryBuilder('p')
            ->where('p.expedienteId IN (:ids)')
            ->setParameter('ids', $expedienteIds)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($orms as $orm) {
            $paso = $this->pasoOrmToDomain($orm);
            if (null === $paso) {
                continue;
            }

            $expedienteId = $paso->expedienteId()->value();
            $result[$expedienteId] ??= [];
            $result[$expedienteId][] = $paso;
        }

        return $result;
    }

    public function findPaso(ExpedienteId $expedienteId, PasoContratacionCliente $paso): ?ContratacionPaso
    {
        $orm = $this->entityManager->getRepository(ContratacionPasoOrm::class)->findOneBy([
            'expedienteId' => $expedienteId->value(),
            'paso' => $paso->value,
        ]);

        return $orm instanceof ContratacionPasoOrm ? $this->pasoOrmToDomain($orm) : null;
    }

    public function saveHito(ExpedienteHito $hito): void
    {
        $orm = new ExpedienteHitoOrm();
        $orm->setId($hito->id());
        $orm->setExpedienteId($hito->expedienteId()->value());
        $orm->setPaso($hito->paso()?->value);
        $orm->setTipo($hito->tipo());
        $orm->setDescripcion($hito->descripcion());
        $orm->setActor($hito->actor()->value);
        $orm->setCreatedAt($hito->createdAt());
        $orm->setReferenciaId($hito->referenciaId());
        $this->entityManager->persist($orm);
        $this->entityManager->flush();
    }

    public function findHitoById(string $id): ?ExpedienteHito
    {
        $orm = $this->entityManager->getRepository(ExpedienteHitoOrm::class)->find($id);

        return $orm instanceof ExpedienteHitoOrm ? $this->hitoOrmToDomain($orm) : null;
    }

    public function findHitosByExpediente(ExpedienteId $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteHitoOrm::class)->findBy(
            ['expedienteId' => $expedienteId->value()],
            ['createdAt' => 'DESC'],
        );

        return array_map($this->hitoOrmToDomain(...), $orms);
    }

    public function findHitosByExpedientePaginated(ExpedienteId $expedienteId, int $offset, int $limit): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteHitoOrm::class)
            ->createQueryBuilder('h')
            ->where('h.expedienteId = :expedienteId')
            ->setParameter('expedienteId', $expedienteId->value())
            ->orderBy('h.createdAt', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();

        return array_map($this->hitoOrmToDomain(...), $orms);
    }

    public function countHitosByExpediente(ExpedienteId $expedienteId): int
    {
        return (int) $this->entityManager->getRepository(ExpedienteHitoOrm::class)
            ->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->where('h.expedienteId = :expedienteId')
            ->setParameter('expedienteId', $expedienteId->value())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecentHitos(int $limit = 20): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteHitoOrm::class)->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit,
        );

        return array_map($this->hitoOrmToDomain(...), $orms);
    }

    public function marcarHitoLeido(string $hitoId): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement(
            'INSERT INTO notificacion_hito_leida (hito_id, leida_at) VALUES (:hitoId, :leidaAt) ON CONFLICT (hito_id) DO NOTHING',
            [
                'hitoId' => $hitoId,
                'leidaAt' => (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function findHitosLeidosIds(): array
    {
        $rows = $this->entityManager->getConnection()->fetchFirstColumn(
            'SELECT hito_id FROM notificacion_hito_leida',
        );

        return array_map(strval(...), $rows);
    }

    private function pasoOrmToDomain(ContratacionPasoOrm $orm): ?ContratacionPaso
    {
        $paso = PasoContratacionCliente::tryFrom($orm->getPaso());
        if (null === $paso) {
            return null;
        }

        return new ContratacionPaso(
            $orm->getId(),
            new ExpedienteId($orm->getExpedienteId()),
            $paso,
            EstadoPasoContratacion::from($orm->getEstado()),
            $orm->getRealizadoAt(),
            $orm->getValidadoAt(),
            $orm->getNotaDevolucion(),
            $orm->getMotivosDevolucion() ?? [],
        );
    }

    private function hitoOrmToDomain(ExpedienteHitoOrm $orm): ExpedienteHito
    {
        return new ExpedienteHito(
            $orm->getId(),
            new ExpedienteId($orm->getExpedienteId()),
            $orm->getTipo(),
            $orm->getDescripcion(),
            ActorHitoExpediente::from($orm->getActor()),
            $orm->getCreatedAt(),
            null !== $orm->getPaso() ? PasoContratacionCliente::tryFrom($orm->getPaso()) : null,
            $orm->getReferenciaId(),
        );
    }
}
