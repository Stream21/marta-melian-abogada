<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\FechaVencimientoFaseParser;
use App\Application\Service\NotificarTramitacionClienteService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedientePresentacionTelematicaRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;

final class AvanzarResolucionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedientePresentacionTelematicaRepositoryInterface $presentacionRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private NotificarTramitacionClienteService $notificar,
        private FechaVencimientoFaseParser $fechaVencimientoParser,
    ) {
    }

    public function __invoke(string $expedienteId, ?string $fechaVencimientoFase): void
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

        $fechaLimite = $this->fechaVencimientoParser->parseRequired($fechaVencimientoFase);

        $actualizado = $expediente
            ->withFaseNegocio(FaseNegocioExpediente::Resolucion, EstadoFaseExpediente::PendienteCliente)
            ->withFechaVencimientoFase($fechaLimite)
            ->touchEstadoCambio();

        $this->expedienteRepository->save($actualizado);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'fase_resolucion_iniciada',
            sprintf(
                'Tramitación completada. El expediente pasa a fase de resolución (plazo hasta %s).',
                $fechaLimite->format('d/m/Y'),
            ),
            ActorHitoExpediente::Sistema,
            new \DateTimeImmutable('now'),
        ));

        if (null !== $actualizado->clienteId() && '' !== $actualizado->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($actualizado->clienteId()));
            if (null !== $cliente) {
                $this->notificar->notificarAvanceResolucion($actualizado, $cliente);
            }
        }
    }
}
