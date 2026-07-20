<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\RequerimientosDuplicadosService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\OrigenDocumentoRequeridoExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\ServicioDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ServicioId;
use App\Domain\ValueObject\TramiteId;

final class InicializarRequerimientosUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ServicioDocumentoRequeridoRepositoryInterface $servicioDocumentoRepository,
        private TramiteDocumentoRequeridoRepositoryInterface $tramiteDocumentoRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $expedienteDocumentoRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
        private RequerimientosDuplicadosService $duplicadosService,
    ) {
    }

    public function __invoke(ExpedienteId $expedienteId): void
    {
        $expediente = $this->expedienteRepository->findById($expedienteId);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            return;
        }

        $esNuevo = 0 === $this->expedienteDocumentoRepository->countByExpediente($expedienteId);

        if ($esNuevo) {
            $this->crearDesdePlantilla($expediente, $expedienteId);

            $this->expedienteRepository->save(
                $expediente
                    ->withEstadoFase(EstadoFaseExpediente::RequerimientosEnProgreso)
                    ->touchEstadoCambio(),
            );

            $this->contratacionRepository->saveHito(new ExpedienteHito(
                bin2hex(random_bytes(16)),
                $expedienteId,
                'fase_requerimientos_iniciada',
                'Contratación completada. Comienza la fase de requerimientos documentales.',
                ActorHitoExpediente::Sistema,
                new \DateTimeImmutable('now'),
            ));

            $this->realtime->publishContratacionUpdate($expedienteId->value(), [
                'type' => 'fase_requerimientos_iniciada',
                'faseNegocio' => FaseNegocioExpediente::Requerimientos->value,
                'actor' => 'sistema',
                'expedienteNumero' => $expediente->numero(),
                'clienteNombre' => $expediente->clientName(),
            ]);
        }

        $this->duplicadosService->reconciliar($expedienteId, $expediente);
    }

    private function crearDesdePlantilla(Expediente $expediente, ExpedienteId $expedienteId): void
    {
        $orden = 0;
        $nombresServicio = [];
        $clavesServicio = [];

        $servicioId = $expediente->servicioId();
        if (null !== $servicioId && '' !== $servicioId) {
            $docsServicio = $this->servicioDocumentoRepository->findByServicioId(new ServicioId($servicioId));
            foreach ($docsServicio as $doc) {
                if (FaseDocumentoTramite::DocumentosCliente !== $doc->fase()) {
                    continue;
                }

                $nombresServicio[$this->duplicadosService->normalizarNombre($doc->nombre())] = true;
                $clave = $this->duplicadosService->claveSemantica($doc->nombre());
                if (null !== $clave) {
                    $clavesServicio[$clave] = true;
                }

                $this->expedienteDocumentoRepository->save(new ExpedienteDocumentoRequerido(
                    new ExpedienteDocumentoRequeridoId(bin2hex(random_bytes(16))),
                    $expedienteId,
                    $doc->nombre(),
                    $doc->descripcion(),
                    $doc->obligatorio(),
                    $doc->tipo(),
                    $doc->maxImagenes(),
                    $orden++,
                    OrigenDocumentoRequeridoExpediente::Servicio,
                    null,
                    $doc->id(),
                ));
            }
        }

        $tramiteId = $expediente->tramiteId();
        if (null !== $tramiteId && '' !== $tramiteId) {
            $docsTramite = $this->tramiteDocumentoRepository->findByTramiteId(new TramiteId($tramiteId));
            foreach ($docsTramite as $doc) {
                if (FaseDocumentoTramite::DocumentosCliente !== $doc->fase()) {
                    continue;
                }

                if ($this->duplicadosService->tramiteDuplicaServicio($doc->nombre(), $nombresServicio, $clavesServicio)) {
                    continue;
                }

                $this->expedienteDocumentoRepository->save(new ExpedienteDocumentoRequerido(
                    new ExpedienteDocumentoRequeridoId(bin2hex(random_bytes(16))),
                    $expedienteId,
                    $doc->nombre(),
                    $doc->descripcion(),
                    $doc->obligatorio(),
                    $doc->tipo(),
                    $doc->maxImagenes(),
                    100 + $orden++,
                    OrigenDocumentoRequeridoExpediente::Tramite,
                    $doc->id(),
                ));
            }
        }
    }
}
