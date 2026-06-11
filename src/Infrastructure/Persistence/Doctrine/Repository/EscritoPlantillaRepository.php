<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\EscritoPlantilla;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\EscritoPlantillaRepositoryInterface;
use App\Domain\ValueObject\EscritoPlantillaId;
use App\Domain\ValueObject\TramiteId;
use App\Infrastructure\Persistence\Doctrine\Entity\EscritoPlantillaOrm;
use Doctrine\ORM\EntityManagerInterface;

final class EscritoPlantillaRepository implements EscritoPlantillaRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(EscritoPlantilla $plantilla): void
    {
        $existing = $this->entityManager->getRepository(EscritoPlantillaOrm::class)->find($plantilla->id()->value());
        $now = new \DateTimeImmutable();

        if ($existing instanceof EscritoPlantillaOrm) {
            $existing->setBloques($plantilla->bloques());
            $existing->setUpdatedAt($now);
        } else {
            $orm = new EscritoPlantillaOrm();
            $orm->setId($plantilla->id()->value());
            $orm->setTramiteId($plantilla->tramiteId()->value());
            $orm->setTipo($plantilla->tipo()->value);
            $orm->setBloques($plantilla->bloques());
            $orm->setCreatedAt($now);
            $orm->setUpdatedAt($now);
            $this->entityManager->persist($orm);
        }

        $this->entityManager->flush();
    }

    public function findByTramiteAndTipo(TramiteId $tramiteId, TipoEscrito $tipo): ?EscritoPlantilla
    {
        $orm = $this->entityManager->getRepository(EscritoPlantillaOrm::class)->findOneBy([
            'tramiteId' => $tramiteId->value(),
            'tipo' => $tipo->value,
        ]);

        return $orm instanceof EscritoPlantillaOrm ? $this->ormToDomain($orm) : null;
    }

    private function ormToDomain(EscritoPlantillaOrm $orm): EscritoPlantilla
    {
        return new EscritoPlantilla(
            new EscritoPlantillaId($orm->getId()),
            new TramiteId($orm->getTramiteId()),
            TipoEscrito::fromString($orm->getTipo()),
            $orm->getBloques(),
            $orm->getCreatedAt(),
            $orm->getUpdatedAt(),
        );
    }
}
