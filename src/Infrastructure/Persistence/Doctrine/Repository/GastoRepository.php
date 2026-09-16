<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Gasto;
use App\Domain\Repository\GastoRepositoryInterface;
use App\Domain\ValueObject\GastoId;
use App\Infrastructure\Persistence\Doctrine\Entity\GastoOrm;
use Doctrine\ORM\EntityManagerInterface;

final class GastoRepository implements GastoRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Gasto $gasto): void
    {
        $existing = $this->entityManager->getRepository(GastoOrm::class)->find($gasto->id()->value());

        if ($existing instanceof GastoOrm) {
            $existing->setConcepto($gasto->concepto());
            $existing->setImporte($gasto->importe());
            $existing->setFecha($gasto->fecha());
            $existing->setCategoria($gasto->categoria());
            $existing->setNotas($gasto->notas());
            $existing->setFacturaPdfPath($gasto->facturaPdfPath());
            $existing->setUpdatedAt($gasto->updatedAt());
        } else {
            $this->entityManager->persist($this->domainToOrm($gasto));
        }

        $this->entityManager->flush();
    }

    public function findById(GastoId $id): ?Gasto
    {
        $orm = $this->entityManager->getRepository(GastoOrm::class)->find($id->value());

        return $orm instanceof GastoOrm ? $this->ormToDomain($orm) : null;
    }

    /**
     * @return Gasto[]
     */
    public function findAll(): array
    {
        $orms = $this->entityManager->getRepository(GastoOrm::class)->findBy([], ['fecha' => 'DESC', 'createdAt' => 'DESC']);

        return array_map($this->ormToDomain(...), $orms);
    }

    public function delete(GastoId $id): void
    {
        $orm = $this->entityManager->getRepository(GastoOrm::class)->find($id->value());
        if (!$orm instanceof GastoOrm) {
            return;
        }

        $this->entityManager->remove($orm);
        $this->entityManager->flush();
    }

    private function ormToDomain(GastoOrm $orm): Gasto
    {
        return new Gasto(
            new GastoId($orm->getId()),
            $orm->getConcepto(),
            $orm->getImporte(),
            $orm->getFecha(),
            $orm->getCategoria(),
            $orm->getNotas(),
            $orm->getFacturaPdfPath(),
            $orm->getCreatedAt(),
            $orm->getUpdatedAt(),
        );
    }

    private function domainToOrm(Gasto $gasto): GastoOrm
    {
        $orm = new GastoOrm();
        $orm->setId($gasto->id()->value());
        $orm->setConcepto($gasto->concepto());
        $orm->setImporte($gasto->importe());
        $orm->setFecha($gasto->fecha());
        $orm->setCategoria($gasto->categoria());
        $orm->setNotas($gasto->notas());
        $orm->setFacturaPdfPath($gasto->facturaPdfPath());
        $orm->setCreatedAt($gasto->createdAt());
        $orm->setUpdatedAt($gasto->updatedAt());

        return $orm;
    }
}
