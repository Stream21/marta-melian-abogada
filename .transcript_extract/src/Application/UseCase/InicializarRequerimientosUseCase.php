<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\OrigenDocumentoRequeridoExpediente;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteId;

final class InicializarRequerimientosUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private TramiteDocumentoRequeridoRepositoryInterface $tramiteDocumentoRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $expedienteDocumentoRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function __invoke(ExpedienteId $expedienteId): void
    {
        if ($this->expedienteDocumentoRepository->countByExpediente($expedienteId) > 0) {
            return;
        }

        $expediente = $this->expedienteRepository->findById($expedienteId);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            return;
        }

        $tramiteId = $expediente->tramiteId();
        if (null !== $tramiteId && '' !== $tramiteId) {
            $docsTramite = $this->tramiteDocumentoRepository->findByTramiteId(new TramiteId($tramiteId));
            $orden = 0;
            foreach ($docsTramite as $doc) {
                if (FaseDocumentoTramite::DocumentosCliente !== $doc->fase()) {
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
                    $orden++,
                    OrigenDocumentoRequeridoExpediente::Tramite,
                    $doc->id(),
                ));
            }
        }

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
}
