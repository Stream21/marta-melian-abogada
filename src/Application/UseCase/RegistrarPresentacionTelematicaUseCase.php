<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\NotificarTramitacionClienteService;
use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\ExpedientePresentacionTelematica;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PlataformaTramitacion;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedientePresentacionTelematicaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedientePresentacionTelematicaId;
use App\Domain\ValueObject\TramiteId;

final class RegistrarPresentacionTelematicaUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedientePresentacionTelematicaRepositoryInterface $presentacionRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private NotificarTramitacionClienteService $notificar,
        private TramitacionSubfaseSyncService $subfaseSync,
    ) {
    }

    /**
     * @param array{content: string, filename: string} $presentacionFile
     * @param array{content: string, filename: string} $justificanteFile
     */
    public function __invoke(
        string $expedienteId,
        array $presentacionFile,
        array $justificanteFile,
        string $fechaPresentacion,
    ): void {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de tramitación.');
        }

        $this->assertMercurio($expediente->tramiteId());

        if (null !== $this->presentacionRepository->findByExpediente($id)) {
            throw new \InvalidArgumentException('Ya existe una presentación telemática registrada.');
        }

        try {
            $fecha = new \DateTimeImmutable($fechaPresentacion);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Fecha de presentación no válida.');
        }

        $presentacionPath = $this->fileStorage->savePdf(
            $id,
            'presentacion-telematica-' . bin2hex(random_bytes(4)) . '.pdf',
            $presentacionFile['content'],
        );
        $justificantePath = $this->fileStorage->savePdf(
            $id,
            'justificante-presentacion-' . bin2hex(random_bytes(4)) . '.pdf',
            $justificanteFile['content'],
        );

        $presentacion = new ExpedientePresentacionTelematica(
            new ExpedientePresentacionTelematicaId(bin2hex(random_bytes(16))),
            $id,
            $presentacionPath,
            $justificantePath,
            '',
            $fecha,
        );
        $this->presentacionRepository->save($presentacion);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'presentacion_telematica_registrada',
            sprintf('Presentación telemática registrada (%s).', $fecha->format('d/m/Y')),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->subfaseSync->sync($expediente);

        if (null !== $expediente->clienteId() && '' !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente) {
                $this->notificar->notificarPresentacionRegistrada($expediente, $cliente);
            }
        }
    }

    private function assertMercurio(?string $tramiteId): void
    {
        if (null === $tramiteId || '' === $tramiteId) {
            return;
        }
        $tramite = $this->tramiteRepository->findById(new TramiteId($tramiteId));
        if (null !== $tramite && PlataformaTramitacion::Mercurio !== $tramite->plataforma()) {
            throw new \InvalidArgumentException(
                'Este flujo de tramitación está disponible solo para trámites en Mercurio.',
            );
        }
    }
}
