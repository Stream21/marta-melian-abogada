<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\ServicioCampoFormulario;
use App\Domain\Entity\TipoCampoFormulario;
use App\Domain\Repository\ServicioCampoFormularioRepositoryInterface;
use App\Domain\ValueObject\ServicioCampoFormularioId;
use App\Domain\ValueObject\ServicioId;
use App\Infrastructure\Persistence\Doctrine\Entity\ServicioCampoFormularioOrm;
use App\Infrastructure\Persistence\Doctrine\Entity\ServicioOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ServicioCampoFormularioRepository implements ServicioCampoFormularioRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findByServicioId(ServicioId $servicioId): array
    {
        $servicioOrm = $this->entityManager->getRepository(ServicioOrm::class)->find($servicioId->value());
        if (!$servicioOrm instanceof ServicioOrm) {
            return [];
        }

        $orms = $this->entityManager
            ->getRepository(ServicioCampoFormularioOrm::class)
            ->findBy(['servicio' => $servicioOrm], ['orden' => 'ASC', 'etiqueta' => 'ASC']);

        return array_map($this->ormToDomain(...), $orms);
    }

    public function replaceForServicio(ServicioId $servicioId, array $campos): void
    {
        $servicioOrm = $this->entityManager->getRepository(ServicioOrm::class)->find($servicioId->value());
        if (!$servicioOrm instanceof ServicioOrm) {
            throw new \InvalidArgumentException('Servicio no encontrado.');
        }

        $existing = $this->entityManager
            ->getRepository(ServicioCampoFormularioOrm::class)
            ->findBy(['servicio' => $servicioOrm]);

        /** @var array<string, ServicioCampoFormularioOrm> $existingById */
        $existingById = [];
        foreach ($existing as $orm) {
            $existingById[$orm->getId()] = $orm;
        }

        $now = new \DateTimeImmutable();
        $incomingIds = [];

        foreach ($campos as $campo) {
            $id = $campo->id()->value();
            $incomingIds[$id] = true;

            if (isset($existingById[$id])) {
                $orm = $existingById[$id];
            } else {
                $orm = new ServicioCampoFormularioOrm();
                $orm->setId($id);
                $orm->setServicio($servicioOrm);
                $orm->setCreatedAt($now);
                $this->entityManager->persist($orm);
            }

            $orm->setClave($campo->clave());
            $orm->setEtiqueta($campo->etiqueta());
            $orm->setTipo($campo->tipo()->value);
            $orm->setOpcionesJson($campo->opcionesJson());
            $orm->setObligatorio($campo->obligatorio());
            $orm->setOrden($campo->orden());
            $orm->setUpdatedAt($now);
        }

        foreach ($existing as $orm) {
            if (!isset($incomingIds[$orm->getId()])) {
                $this->entityManager->remove($orm);
            }
        }

        $this->entityManager->flush();
    }

    private function ormToDomain(ServicioCampoFormularioOrm $orm): ServicioCampoFormulario
    {
        return new ServicioCampoFormulario(
            new ServicioCampoFormularioId($orm->getId()),
            new ServicioId($orm->getServicio()->getId()),
            $orm->getClave(),
            $orm->getEtiqueta(),
            TipoCampoFormulario::from($orm->getTipo()),
            $orm->isObligatorio(),
            $orm->getOrden(),
            $orm->getOpcionesJson(),
        );
    }
}
