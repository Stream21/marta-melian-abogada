<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Expediente;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ExpedienteRepository implements ExpedienteRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Expediente $expediente): void
    {
        $existing = $this->entityManager->getRepository(ExpedienteOrm::class)->find($expediente->id()->value());

        if ($existing instanceof ExpedienteOrm) {
            $existing->setNumero($expediente->numero());
            $existing->setTitulo($expediente->titulo());
            $existing->setEstado($expediente->estado()->value);
            $existing->setFechaApertura($expediente->fechaApertura());
            $existing->setClientName($expediente->clientName());
            $existing->setCaseReference($expediente->caseReference());
            $existing->setFolderPath($expediente->folderPath());
            $existing->setPaymentStatus($expediente->paymentStatus());
        } else {
            $this->entityManager->persist($this->domainToOrm($expediente));
        }

        $this->entityManager->flush();
    }

    public function findById(ExpedienteId $id): ?Expediente
    {
        $orm = $this->entityManager->getRepository(ExpedienteOrm::class)->find($id->value());

        return $orm instanceof ExpedienteOrm ? $this->ormToDomain($orm) : null;
    }

    /**
     * @return Expediente[]
     */
    public function findAll(): array
    {
        $orms = $this->entityManager->getRepository(ExpedienteOrm::class)->findBy([], ['fechaApertura' => 'DESC']);

        return array_map($this->ormToDomain(...), $orms);
    }

    public function remove(Expediente $expediente): void
    {
        $orm = $this->entityManager->getRepository(ExpedienteOrm::class)->find($expediente->id()->value());
        if ($orm instanceof ExpedienteOrm) {
            $this->entityManager->remove($orm);
            $this->entityManager->flush();
        }
    }

    private function ormToDomain(ExpedienteOrm $orm): Expediente
    {
        return new Expediente(
            new ExpedienteId($orm->getId()),
            $orm->getNumero(),
            $orm->getTitulo(),
            EstadoExpediente::from($orm->getEstado()),
            $orm->getFechaApertura(),
            $orm->getClientName(),
            $orm->getCaseReference(),
            $orm->getFolderPath(),
            $orm->getPaymentStatus(),
        );
    }

    private function domainToOrm(Expediente $expediente): ExpedienteOrm
    {
        $orm = new ExpedienteOrm();
        $orm->setId($expediente->id()->value());
        $orm->setNumero($expediente->numero());
        $orm->setTitulo($expediente->titulo());
        $orm->setEstado($expediente->estado()->value);
        $orm->setFechaApertura($expediente->fechaApertura());
        $orm->setClientName($expediente->clientName());
        $orm->setCaseReference($expediente->caseReference());
        $orm->setFolderPath($expediente->folderPath());
        $orm->setPaymentStatus($expediente->paymentStatus());

        return $orm;
    }
}
