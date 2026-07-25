<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\NotificarTramitacionClienteService;
use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedientePresentacionTelematicaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;

final class RegistrarSeguimientoExtranjeriaUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedientePresentacionTelematicaRepositoryInterface $presentacionRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private NotificarTramitacionClienteService $notificar,
        private TramitacionSubfaseSyncService $subfaseSync,
    ) {
    }

    public function __invoke(string $expedienteId, string $numeroExpedienteExtranjeria): void
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
        if (null === $presentacion) {
            throw new \InvalidArgumentException('Registre primero la presentación telemática.');
        }

        $actualizada = $presentacion->withSeguimiento($numeroExpedienteExtranjeria);
        $this->presentacionRepository->save($actualizada);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'seguimiento_extranjeria_asignado',
            sprintf(
                'Número de expediente de extranjería asignado: %s.',
                $actualizada->numeroExpedienteExtranjeria(),
            ),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->subfaseSync->sync($expediente);

        if (null !== $expediente->clienteId() && '' !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente) {
                $this->notificar->notificarSeguimientoAsignado(
                    $expediente,
                    $cliente,
                    (string) $actualizada->numeroExpedienteExtranjeria(),
                );
            }
        }
    }
}
