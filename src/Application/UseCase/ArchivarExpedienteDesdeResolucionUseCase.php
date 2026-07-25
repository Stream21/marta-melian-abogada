<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\ExpedienteResolucionRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ArchivarExpedienteDesdeResolucionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteResolucionRepositoryInterface $resolucionRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
    ) {
    }

    public function __invoke(string $expedienteId): void
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Resolucion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('Solo se archiva desde la fase de resolución.');
        }
        if (!$expediente->estado()->isOperativo()) {
            throw new \InvalidArgumentException('El expediente no está abierto.');
        }

        $resolucion = $this->resolucionRepository->findByExpediente($id);
        if (null === $resolucion) {
            throw new \InvalidArgumentException('Registre la resolución antes de archivar.');
        }
        if (
            'concedida' === $resolucion->outcome()->value
            && !$resolucion->gestionesCompletas()
        ) {
            throw new \InvalidArgumentException(
                'Marque todas las gestiones adicionales como hechas antes de archivar, o complete el checklist.',
            );
        }

        $this->expedienteRepository->save($expediente->archivar());

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'expediente_archivado',
            'Expediente archivado tras resolución.',
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));
    }
}
