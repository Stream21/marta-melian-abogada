<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ActorResponsableDocumento;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class AsignarDocumentoRequerimientoAlAbogadoUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function __invoke(string $expedienteId, string $documentoId): void
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de requerimientos.');
        }

        $docReqId = new ExpedienteDocumentoRequeridoId($documentoId);
        $doc = $this->documentoRequeridoRepository->findById($docReqId);
        if (null === $doc || !$doc->expedienteId()->equals($id)) {
            throw new \InvalidArgumentException('Documento requerido no encontrado.');
        }

        $entrega = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento($id, $docReqId);

        if (null === $entrega) {
            $entrega = new ExpedienteDocumentoEntregado(
                bin2hex(random_bytes(16)),
                $id,
                null,
                $docReqId,
                '',
                EstadoDocumentoEntregado::Pendiente,
                new \DateTimeImmutable('now'),
                null,
                responsableActual: ActorResponsableDocumento::Abogado,
            );
        } else {
            $entrega = $entrega->asignarAlAbogado();
        }

        $this->documentoEntregadoRepository->save($entrega);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'documento_requerimientos_asignado_abogado',
            sprintf('El abogado ha tomado a su cargo el documento «%s».', $doc->nombre()),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->sincronizarEstadoExpediente($id, $expediente);

        $this->realtime->publishContratacionUpdate($expedienteId, [
            'type' => 'documento_requerimientos_asignado_abogado',
            'documentoId' => $documentoId,
            'documentoNombre' => $doc->nombre(),
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }

    private function sincronizarEstadoExpediente(ExpedienteId $id, \App\Domain\Entity\Expediente $expediente): void
    {
        if (EstadoFaseExpediente::RequerimientosListo === $expediente->estadoFase()) {
            $this->expedienteRepository->save(
                $expediente
                    ->withEstadoFase(EstadoFaseExpediente::RequerimientosEnProgreso)
                    ->touchEstadoCambio(),
            );
        }
    }
}
