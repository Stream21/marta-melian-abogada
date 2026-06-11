<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Entity\TramiteDocumentoRequerido;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;
use App\Domain\ValueObject\TramiteId;
use App\Infrastructure\Persistence\Doctrine\Entity\TramiteDocumentoRequeridoOrm;
use App\Infrastructure\Persistence\Doctrine\Entity\TramiteOrm;
use Doctrine\ORM\EntityManagerInterface;

final class TramiteDocumentoRequeridoRepository implements TramiteDocumentoRequeridoRepositoryInterface
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
            ->getRepository(TramiteDocumentoRequeridoOrm::class)
            ->findBy(['tramite' => $tramiteOrm], ['orden' => 'ASC', 'nombre' => 'ASC']);

        return array_map($this->ormToDomain(...), $orms);
    }

    public function replaceForTramite(TramiteId $tramiteId, array $documentos): void
    {
        $tramiteOrm = $this->entityManager->getRepository(TramiteOrm::class)->find($tramiteId->value());
        if (!$tramiteOrm instanceof TramiteOrm) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $existing = $this->entityManager
            ->getRepository(TramiteDocumentoRequeridoOrm::class)
            ->findBy(['tramite' => $tramiteOrm]);

        /** @var array<string, TramiteDocumentoRequeridoOrm> $existingById */
        $existingById = [];
        foreach ($existing as $orm) {
            $existingById[$orm->getId()] = $orm;
        }

        $now = new \DateTimeImmutable();
        $incomingIds = [];

        foreach ($documentos as $documento) {
            $id = $documento->id()->value();
            $incomingIds[$id] = true;

            if (isset($existingById[$id])) {
                $orm = $existingById[$id];
            } else {
                $orm = new TramiteDocumentoRequeridoOrm();
                $orm->setId($id);
                $orm->setTramite($tramiteOrm);
                $orm->setCreatedAt($now);
                $this->entityManager->persist($orm);
            }

            $orm->setFase($documento->fase()->value);
            $orm->setNombre($documento->nombre());
            $orm->setDescripcion($documento->descripcion());
            $orm->setObligatorio($documento->obligatorio());
            $orm->setTipo($documento->tipo()->value);
            $orm->setMaxImagenes($documento->maxImagenes());
            $orm->setFormatos($documento->formatos());
            $orm->setOrden($documento->orden());
            $orm->setUpdatedAt($now);
        }

        foreach ($existing as $orm) {
            if (!isset($incomingIds[$orm->getId()])) {
                $this->entityManager->remove($orm);
            }
        }

        $this->entityManager->flush();
    }

    private function ormToDomain(TramiteDocumentoRequeridoOrm $orm): TramiteDocumentoRequerido
    {
        return new TramiteDocumentoRequerido(
            new TramiteDocumentoRequeridoId($orm->getId()),
            new TramiteId($orm->getTramite()->getId()),
            FaseDocumentoTramite::fromValue($orm->getFase()),
            $orm->getNombre(),
            $orm->getDescripcion(),
            $orm->isObligatorio(),
            TipoDocumentoRequerido::fromString($orm->getTipo()),
            $orm->getMaxImagenes(),
            $orm->getFormatos(),
            $orm->getOrden(),
        );
    }
}
