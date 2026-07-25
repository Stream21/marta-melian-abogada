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

final class ActualizarGestionesResolucionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteResolucionRepositoryInterface $resolucionRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
    ) {
    }

    /**
     * @param list<array{id: string, hecho: bool}> $updates
     */
    public function __invoke(string $expedienteId, array $updates): void
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Resolucion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de resolución.');
        }

        $resolucion = $this->resolucionRepository->findByExpediente($id);
        if (null === $resolucion) {
            throw new \InvalidArgumentException('No hay resolución registrada.');
        }

        $map = [];
        foreach ($updates as $u) {
            if (isset($u['id'])) {
                $map[(string) $u['id']] = (bool) ($u['hecho'] ?? false);
            }
        }

        $nuevas = [];
        foreach ($resolucion->gestiones() as $g) {
            $nuevas[] = isset($map[$g->id()]) ? $g->withHecho($map[$g->id()]) : $g;
        }

        $this->resolucionRepository->save($resolucion->withGestiones($nuevas));

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'resolucion_gestiones_actualizadas',
            'Gestiones post-resolución actualizadas.',
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));
    }
}
