<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ExpedienteRequerimientoCampo;
use App\Domain\Entity\TipoCampoFormulario;
use App\Domain\Repository\ExpedienteRequerimientoCampoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteRequerimientoCampoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteRequerimientoCampoOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteRequerimientoCampoRepository implements ExpedienteRequerimientoCampoRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExpedienteRequerimientoCampo $campo): void
    {
        $this->persist($campo);
        $this->entityManager->flush();
    }

    public function saveAll(array $campos): void
    {
        foreach ($campos as $campo) {
            $this->persist($campo);
        }
        $this->entityManager->flush();
    }

    public function findById(ExpedienteRequerimientoCampoId $id): ?ExpedienteRequerimientoCampo
    {
        $orm = $this->entityManager->find(ExpedienteRequerimientoCampoOrm::class, $id->value());

        return $orm instanceof ExpedienteRequerimientoCampoOrm ? $this->ormToDomain($orm) : null;
    }

    public function findByRequerimientoId(ExpedienteRequerimientoMercurioId $requerimientoId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteRequerimientoCampoOrm::class)->findBy(
            ['requerimientoId' => $requerimientoId->value()],
            ['orden' => 'ASC', 'etiqueta' => 'ASC'],
        );

        return array_map($this->ormToDomain(...), $orms);
    }

    public function delete(ExpedienteRequerimientoCampoId $id): void
    {
        $orm = $this->entityManager->find(ExpedienteRequerimientoCampoOrm::class, $id->value());
        if (!$orm instanceof ExpedienteRequerimientoCampoOrm) {
            return;
        }

        $this->entityManager->remove($orm);
        $this->entityManager->flush();
    }

    public function deleteByRequerimientoId(ExpedienteRequerimientoMercurioId $id): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(ExpedienteRequerimientoCampoOrm::class, 'c')
            ->where('c.requerimientoId = :requerimientoId')
            ->setParameter('requerimientoId', $id->value())
            ->getQuery()
            ->execute();
    }

    private function persist(ExpedienteRequerimientoCampo $campo): void
    {
        $orm = $this->entityManager->find(ExpedienteRequerimientoCampoOrm::class, $campo->id()->value())
            ?? new ExpedienteRequerimientoCampoOrm();

        $now = new \DateTimeImmutable();
        if (!$this->entityManager->contains($orm)) {
            $orm->setId($campo->id()->value());
            $orm->setCreatedAt($now);
            $this->entityManager->persist($orm);
        }

        $orm->setRequerimientoId($campo->requerimientoId()->value());
        $orm->setClave($campo->clave());
        $orm->setEtiqueta($campo->etiqueta());
        $orm->setTipo($campo->tipo()->value);
        $orm->setOpcionesJson($campo->opcionesJson());
        $orm->setObligatorio($campo->obligatorio());
        $orm->setOrden($campo->orden());
        $orm->setValor($campo->valor());
        $orm->setUpdatedAt($now);
    }

    private function ormToDomain(ExpedienteRequerimientoCampoOrm $orm): ExpedienteRequerimientoCampo
    {
        return new ExpedienteRequerimientoCampo(
            new ExpedienteRequerimientoCampoId($orm->getId()),
            new ExpedienteRequerimientoMercurioId($orm->getRequerimientoId()),
            $orm->getClave(),
            $orm->getEtiqueta(),
            TipoCampoFormulario::from($orm->getTipo()),
            $orm->isObligatorio(),
            $orm->getOrden(),
            $orm->getOpcionesJson(),
            $orm->getValor(),
        );
    }
}
