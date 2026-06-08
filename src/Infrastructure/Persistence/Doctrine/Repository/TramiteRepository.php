<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\PlataformaTramitacion;
use App\Domain\Entity\Tramite;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ServicioId;
use App\Domain\ValueObject\TramiteId;
use App\Infrastructure\Persistence\Doctrine\Entity\ServicioOrm;
use App\Infrastructure\Persistence\Doctrine\Entity\TramiteOrm;
use Doctrine\ORM\EntityManagerInterface;

final class TramiteRepository implements TramiteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findAll(bool $incluirInactivos = false, ?ServicioId $servicioId = null): array
    {
        $qb = $this->entityManager
            ->getRepository(TramiteOrm::class)
            ->createQueryBuilder('t')
            ->innerJoin('t.servicio', 's')
            ->addSelect('s')
            ->orderBy('s.nombre', 'ASC')
            ->addOrderBy('t.nombre', 'ASC');

        if (!$incluirInactivos) {
            $qb->andWhere('t.activo = true');
        }

        if ($servicioId instanceof ServicioId) {
            $qb->andWhere('t.servicio = :servicioId')
                ->setParameter('servicioId', $servicioId->value());
        }

        $orms = $qb->getQuery()->getResult();

        return array_map($this->ormToDomain(...), $orms);
    }

    public function findById(TramiteId $id): ?Tramite
    {
        $orm = $this->entityManager
            ->getRepository(TramiteOrm::class)
            ->createQueryBuilder('t')
            ->innerJoin('t.servicio', 's')
            ->addSelect('s')
            ->where('t.id = :id')
            ->setParameter('id', $id->value())
            ->getQuery()
            ->getOneOrNullResult();

        return $orm instanceof TramiteOrm ? $this->ormToDomain($orm) : null;
    }

    public function findByNombreNormalized(
        ServicioId $servicioId,
        string $normalizedNombre,
        ?TramiteId $excluirId = null,
    ): ?Tramite {
        $qb = $this->entityManager
            ->getRepository(TramiteOrm::class)
            ->createQueryBuilder('t')
            ->innerJoin('t.servicio', 's')
            ->addSelect('s')
            ->where('t.servicio = :servicioId')
            ->andWhere('LOWER(TRIM(t.nombre)) = :n')
            ->setParameter('servicioId', $servicioId->value())
            ->setParameter('n', $normalizedNombre)
            ->setMaxResults(1);

        if ($excluirId instanceof TramiteId) {
            $qb->andWhere('t.id <> :excluir')->setParameter('excluir', $excluirId->value());
        }

        $orm = $qb->getQuery()->getOneOrNullResult();

        return $orm instanceof TramiteOrm ? $this->ormToDomain($orm) : null;
    }

    public function save(Tramite $tramite): void
    {
        $existing = $this->entityManager->getRepository(TramiteOrm::class)->find($tramite->id()->value());
        $servicioOrm = $this->entityManager->getRepository(ServicioOrm::class)->find($tramite->servicioId()->value());

        if (!$servicioOrm instanceof ServicioOrm) {
            throw new \InvalidArgumentException('Servicio no encontrado.');
        }

        $now = new \DateTimeImmutable();

        if ($existing instanceof TramiteOrm) {
            $existing->setServicio($servicioOrm);
            $existing->setNombre($tramite->nombre());
            $existing->setHonorarios($tramite->honorarios());
            $existing->setPlataforma($tramite->plataforma()->value);
            $existing->setRequiereProcurador($tramite->requiereProcurador());
            $existing->setActivo($tramite->activo());
            $existing->setUpdatedAt($now);
        } else {
            $orm = $this->domainToOrm($tramite, $servicioOrm);
            $orm->setCreatedAt($now);
            $orm->setUpdatedAt($now);
            $this->entityManager->persist($orm);
        }

        $this->entityManager->flush();
    }

    private function ormToDomain(TramiteOrm $orm): Tramite
    {
        return new Tramite(
            new TramiteId($orm->getId()),
            new ServicioId($orm->getServicio()->getId()),
            $orm->getNombre(),
            $orm->getHonorarios(),
            PlataformaTramitacion::fromString($orm->getPlataforma()),
            $orm->isRequiereProcurador(),
            $orm->isActivo(),
            $orm->getServicio()->getNombre(),
            $orm->getCreatedAt(),
            $orm->getUpdatedAt(),
        );
    }

    private function domainToOrm(Tramite $tramite, ServicioOrm $servicioOrm): TramiteOrm
    {
        $orm = new TramiteOrm();
        $orm->setId($tramite->id()->value());
        $orm->setServicio($servicioOrm);
        $orm->setNombre($tramite->nombre());
        $orm->setHonorarios($tramite->honorarios());
        $orm->setPlataforma($tramite->plataforma()->value);
        $orm->setRequiereProcurador($tramite->requiereProcurador());
        $orm->setActivo($tramite->activo());

        return $orm;
    }
}
