<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\SubfaseTramitacion;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedientePresentacionTelematicaRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class AvanzarResolucionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedientePresentacionTelematicaRepositoryInterface $presentacionRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
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
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de tramitación.');
        }

        $presentacion = $this->presentacionRepository->findByExpediente($id);
        if (null === $presentacion || null === $presentacion->numeroExpedienteExtranjeria()) {
            throw new \InvalidArgumentException(
                'Debe registrar la presentación y el número de expediente de extranjería antes de avanzar.',
            );
        }

        if ($this->requerimientoRepository->countAbiertosByExpediente($id) > 0) {
            throw new \InvalidArgumentException('Hay requerimientos Mercurio abiertos.');
        }

        $this->expedienteRepository->save(
            $expediente
                ->withFaseNegocio(FaseNegocioExpediente::Resolucion, EstadoFaseExpediente::PendienteCliente)
                ->withSubfaseTramitacion(SubfaseTramitacion::ListoResolucion)
                ->touchEstadoCambio(),
        );

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'fase_resolucion_iniciada',
            'Tramitación completada. El expediente pasa a fase de resolución.',
            ActorHitoExpediente::Sistema,
            new \DateTimeImmutable('now'),
        ));
    }
}
