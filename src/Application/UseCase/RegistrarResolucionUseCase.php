<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\GestionesPostResolucionFactory;
use App\Application\Service\NotificarResolucionClienteService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\ExpedienteResolucion;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\OutcomeResolucion;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\ExpedienteResolucionRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteResolucionId;
use App\Domain\ValueObject\TramiteId;

final class RegistrarResolucionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteResolucionRepositoryInterface $resolucionRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private GestionesPostResolucionFactory $gestionesFactory,
        private NotificarResolucionClienteService $notificar,
    ) {
    }

    /**
     * @param array{content: string, filename: string} $archivo
     */
    public function __invoke(
        string $expedienteId,
        array $archivo,
        string $outcomeRaw,
        string $fechaNotificacion,
    ): void {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Resolucion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de resolución.');
        }
        if (!$expediente->estado()->isOperativo()) {
            throw new \InvalidArgumentException('El expediente no está abierto.');
        }
        if (null !== $this->resolucionRepository->findByExpediente($id)) {
            throw new \InvalidArgumentException('Ya existe una resolución registrada.');
        }

        try {
            $outcome = OutcomeResolucion::from($outcomeRaw);
        } catch (\ValueError) {
            throw new \InvalidArgumentException('Indique si la resolución es concedida o denegada.');
        }

        try {
            $fecha = new \DateTimeImmutable($fechaNotificacion);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Fecha de notificación no válida.');
        }

        $path = $this->fileStorage->savePdf(
            $id,
            'resolucion-' . bin2hex(random_bytes(4)) . '.pdf',
            $archivo['content'],
        );

        $tramite = null;
        if (null !== $expediente->tramiteId() && '' !== $expediente->tramiteId()) {
            $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));
        }

        $gestiones = $this->gestionesFactory->crearPara($outcome, $tramite);

        $resolucion = new ExpedienteResolucion(
            new ExpedienteResolucionId(bin2hex(random_bytes(16))),
            $id,
            $outcome,
            $path,
            $fecha,
            $gestiones,
        );
        $this->resolucionRepository->save($resolucion);

        $this->expedienteRepository->save(
            $expediente
                ->withFaseNegocio(FaseNegocioExpediente::Resolucion, EstadoFaseExpediente::Completada)
                ->touchEstadoCambio(),
        );

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'resolucion_registrada',
            sprintf('Resolución %s registrada (%s).', $outcome->label(), $fecha->format('d/m/Y')),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        if (null !== $expediente->clienteId() && '' !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente) {
                $this->notificar->notificarResolucionRegistrada($expediente, $cliente, $outcome);
            }
        }
    }
}
