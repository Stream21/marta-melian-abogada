<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\RequerimientosProgresoCalculator;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class ValidarDocumentoRequerimientosUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private RequerimientosProgresoCalculator $progresoCalculator,
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
        if (null === $entrega || EstadoDocumentoEntregado::Entregado !== $entrega->estado()) {
            throw new \InvalidArgumentException('No hay un documento pendiente de revisión.');
        }

        $this->documentoEntregadoRepository->save($entrega->marcarValidado());

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'documento_requerimientos_validado',
            sprintf('El abogado ha validado el documento «%s».', $doc->nombre()),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
            null,
            $documentoId,
        ));

        $this->sincronizarEstadoExpediente($id, $expediente);

        $this->realtime->publishContratacionUpdate($expedienteId, [
            'type' => 'documento_requerimientos_validado',
            'documentoId' => $documentoId,
            'documentoNombre' => $doc->nombre(),
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }

    private function sincronizarEstadoExpediente(ExpedienteId $id, \App\Domain\Entity\Expediente $expediente): void
    {
        $entregasPorDocId = [];
        foreach ($this->documentoEntregadoRepository->findByExpediente($id) as $entrega) {
            $docId = $entrega->expedienteDocumentoRequeridoId();
            if (null !== $docId) {
                $entregasPorDocId[$docId->value()] = $entrega;
            }
        }

        $documentos = $this->documentoRequeridoRepository->findByExpediente($id);
        $progreso = $this->progresoCalculator->calcular($documentos, $entregasPorDocId);

        if ($progreso['requerimientosListo']) {
            $this->expedienteRepository->save(
                $expediente
                    ->withEstadoFase(EstadoFaseExpediente::RequerimientosListo)
                    ->touchEstadoCambio(),
            );
        }
    }
}
