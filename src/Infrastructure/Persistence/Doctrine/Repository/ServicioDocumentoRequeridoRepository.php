<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Entity\ServicioDocumentoRequerido;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\ServicioDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ServicioDocumentoRequeridoId;
use App\Domain\ValueObject\ServicioId;
use App\Infrastructure\Persistence\Doctrine\Entity\ServicioDocumentoRequeridoOrm;
use App\Infrastructure\Persistence\Doctrine\Entity\ServicioOrm;
use Doctrine\ORM\EntityManagerInterface;

final class ServicioDocumentoRequeridoRepository implements ServicioDocumentoRequeridoRepositoryInterface
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
            ->getRepository(ServicioDocumentoRequeridoOrm::class)
            ->findBy(['servicio' => $servicioOrm], ['orden' => 'ASC', 'nombre' => 'ASC']);

        return array_map($this->ormToDomain(...), $orms);
    }

    public function replaceForServicio(ServicioId $servicioId, array $documentos): void
    {
        $servicioOrm = $this->entityManager->getRepository(ServicioOrm::class)->find($servicioId->value());
        if (!$servicioOrm instanceof ServicioOrm) {
            throw new \InvalidArgumentException('Servicio no encontrado.');
        }

        $existing = $this->entityManager
            ->getRepository(ServicioDocumentoRequeridoOrm::class)
            ->findBy(['servicio' => $servicioOrm]);

        /** @var array<string, ServicioDocumentoRequeridoOrm> $existingById */
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
                $orm = new ServicioDocumentoRequeridoOrm();
                $orm->setId($id);
                $orm->setServicio($servicioOrm);
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

    private function ormToDomain(ServicioDocumentoRequeridoOrm $orm): ServicioDocumentoRequerido
    {
        return new ServicioDocumentoRequerido(
            new ServicioDocumentoRequeridoId($orm->getId()),
            new ServicioId($orm->getServicio()->getId()),
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
