<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\TipoCampoFormulario;
use App\Domain\Entity\TramiteCampoFormulario;
use App\Domain\Repository\TramiteCampoFormularioRepositoryInterface;
use App\Domain\ValueObject\TramiteCampoFormularioId;
use App\Domain\ValueObject\TramiteId;
use App\Infrastructure\Persistence\Doctrine\Entity\TramiteCampoFormularioOrm;
use App\Infrastructure\Persistence\Doctrine\Entity\TramiteOrm;
use Doctrine\ORM\EntityManagerInterface;

final class TramiteCampoFormularioRepository implements TramiteCampoFormularioRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findByTramiteId(TramiteId $tramiteId): array
    {
        $tramiteOrm = $this->entityManager->getRepository(TramiteOrm::class)->find($tramiteId->value());
        if (!$tramiteOrm instanceof TramiteOrm) {
            return [];
        }

        $orms = $this->entityManager
            ->getRepository(TramiteCampoFormularioOrm::class)
            ->findBy(['tramite' => $tramiteOrm], ['orden' => 'ASC', 'etiqueta' => 'ASC']);

        return array_map($this->ormToDomain(...), $orms);
    }

    public function replaceForTramite(TramiteId $tramiteId, array $campos): void
    {
        $tramiteOrm = $this->entityManager->getRepository(TramiteOrm::class)->find($tramiteId->value());
        if (!$tramiteOrm instanceof TramiteOrm) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $existing = $this->entityManager
            ->getRepository(TramiteCampoFormularioOrm::class)
            ->findBy(['tramite' => $tramiteOrm]);

        /** @var array<string, TramiteCampoFormularioOrm> $existingById */
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
                $orm = new TramiteCampoFormularioOrm();
                $orm->setId($id);
                $orm->setTramite($tramiteOrm);
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

    private function ormToDomain(TramiteCampoFormularioOrm $orm): TramiteCampoFormulario
    {
        return new TramiteCampoFormulario(
            new TramiteCampoFormularioId($orm->getId()),
            new TramiteId($orm->getTramite()->getId()),
            $orm->getClave(),
            $orm->getEtiqueta(),
            TipoCampoFormulario::from($orm->getTipo()),
            $orm->isObligatorio(),
            $orm->getOrden(),
            $orm->getOpcionesJson(),
        );
    }
}
