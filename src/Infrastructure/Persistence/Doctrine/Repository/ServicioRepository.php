<?php



declare(strict_types=1);



namespace App\Infrastructure\Persistence\Doctrine\Repository;



use App\Domain\Entity\Servicio;

use App\Domain\Repository\ServicioRepositoryInterface;

use App\Domain\ValueObject\AreaJuridicaId;

use App\Domain\ValueObject\ServicioId;

use App\Infrastructure\Persistence\Doctrine\Entity\AreaJuridicaOrm;

use App\Infrastructure\Persistence\Doctrine\Entity\ServicioOrm;

use Doctrine\ORM\EntityManagerInterface;



final class ServicioRepository implements ServicioRepositoryInterface

{

    public function __construct(

        private EntityManagerInterface $entityManager,

    ) {

    }



    public function findAll(bool $incluirInactivos = false): array

    {

        $qb = $this->entityManager

            ->getRepository(ServicioOrm::class)

            ->createQueryBuilder('s')

            ->innerJoin('s.areaJuridica', 'a')

            ->addSelect('a')

            ->orderBy('a.nombre', 'ASC')

            ->addOrderBy('s.nombre', 'ASC');



        if (!$incluirInactivos) {

            $qb->andWhere('s.activo = true');

        }



        $orms = $qb->getQuery()->getResult();



        return array_map($this->ormToDomain(...), $orms);

    }



    public function findById(ServicioId $id): ?Servicio

    {

        $orm = $this->entityManager

            ->getRepository(ServicioOrm::class)

            ->createQueryBuilder('s')

            ->innerJoin('s.areaJuridica', 'a')

            ->addSelect('a')

            ->where('s.id = :id')

            ->setParameter('id', $id->value())

            ->getQuery()

            ->getOneOrNullResult();



        return $orm instanceof ServicioOrm ? $this->ormToDomain($orm) : null;

    }



    public function findByNombreNormalized(string $normalizedNombre, ?ServicioId $excluirId = null): ?Servicio

    {

        $qb = $this->entityManager

            ->getRepository(ServicioOrm::class)

            ->createQueryBuilder('s')

            ->innerJoin('s.areaJuridica', 'a')

            ->addSelect('a')

            ->where('LOWER(TRIM(s.nombre)) = :n')

            ->setParameter('n', $normalizedNombre)

            ->setMaxResults(1);



        if ($excluirId instanceof ServicioId) {

            $qb->andWhere('s.id <> :excluir')->setParameter('excluir', $excluirId->value());

        }



        $orm = $qb->getQuery()->getOneOrNullResult();



        return $orm instanceof ServicioOrm ? $this->ormToDomain($orm) : null;

    }



    public function save(Servicio $servicio): void

    {

        $existing = $this->entityManager->getRepository(ServicioOrm::class)->find($servicio->id()->value());

        $areaOrm = $this->entityManager->getReference(

            AreaJuridicaOrm::class,

            $servicio->areaJuridicaId()->value(),

        );



        $now = new \DateTimeImmutable();

        if ($existing instanceof ServicioOrm) {

            $existing->setNombre($servicio->nombre());

            $existing->setAreaJuridica($areaOrm);

            $existing->setActivo($servicio->activo());

            $existing->setUpdatedAt($now);

        } else {

            $orm = $this->domainToOrm($servicio, $areaOrm);

            $orm->setCreatedAt($now);

            $orm->setUpdatedAt($now);

            $this->entityManager->persist($orm);

        }



        $this->entityManager->flush();

    }



    private function ormToDomain(ServicioOrm $orm): Servicio

    {

        return new Servicio(

            new ServicioId($orm->getId()),

            $orm->getNombre(),

            new AreaJuridicaId($orm->getAreaJuridica()->getId()),

            $orm->isActivo(),

            $orm->getAreaJuridica()->getCodigo(),

            $orm->getAreaJuridica()->getNombre(),

            $orm->getCreatedAt(),

            $orm->getUpdatedAt(),

        );

    }



    private function domainToOrm(Servicio $servicio, AreaJuridicaOrm $areaOrm): ServicioOrm

    {

        $orm = new ServicioOrm();

        $orm->setId($servicio->id()->value());

        $orm->setNombre($servicio->nombre());

        $orm->setAreaJuridica($areaOrm);

        $orm->setActivo($servicio->activo());



        return $orm;

    }

}

