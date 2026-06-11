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
        } else {
            $orm = new ContratacionPasoOrm();
            $orm->setId($paso->id());
            $orm->setExpedienteId($paso->expedienteId()->value());
            $orm->setPaso($paso->paso()->value);
            $orm->setEstado($paso->estado()->value);
            $orm->setRealizadoAt($paso->realizadoAt());
            $orm->setValidadoAt($paso->validadoAt());
            $this->entityManager->persist($orm);
        }

        $this->entityManager->flush();
    }

    public function findPasosByExpediente(ExpedienteId $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(ContratacionPasoOrm::class)->findBy(
            ['expedienteId' => $expedienteId->value()],
        );

        return array_map($this->pasoOrmToDomain(...), $orms);
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
        $this->entityManager->persist($orm);
        $this->entityManager->flush();
    }

    public function findHitosByExpediente(ExpedienteId $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteHitoOrm::class)->findBy(
            ['expedienteId' => $expedienteId->value()],
            ['createdAt' => 'DESC'],
        );

        return array_map($this->hitoOrmToDomain(...), $orms);
    }

    private function pasoOrmToDomain(ContratacionPasoOrm $orm): ContratacionPaso
    {
        return new ContratacionPaso(
            $orm->getId(),
            new ExpedienteId($orm->getExpedienteId()),
            PasoContratacionCliente::from($orm->getPaso()),
            EstadoPasoContratacion::from($orm->getEstado()),
            $orm->getRealizadoAt(),
            $orm->getValidadoAt(),
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
            null !== $orm->getPaso() ? PasoContratacionCliente::from($orm->getPaso()) : null,
        );
    }
}
