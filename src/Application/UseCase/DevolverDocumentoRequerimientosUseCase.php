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
use App\Domain\Entity\SubidoPorDocumento;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class DevolverDocumentoRequerimientosUseCase
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

    public function __invoke(string $expedienteId, string $documentoId, string $nota): void
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
            throw new \InvalidArgumentException('No hay un documento entregado para devolver.');
        }

        $pendienteRevision = EstadoDocumentoEntregado::Entregado === $entrega->estado()
            && SubidoPorDocumento::Cliente === $entrega->subidoPor();
        $validadoPorAbogado = EstadoDocumentoEntregado::Validado === $entrega->estado()
            && SubidoPorDocumento::Cliente === $entrega->subidoPor();

        if (!$pendienteRevision && !$validadoPorAbogado) {
            throw new \InvalidArgumentException('Este documento no puede devolverse al cliente en su estado actual.');
        }

        $this->documentoEntregadoRepository->save($entrega->marcarRechazado($nota));

        $this->sincronizarEstadoExpediente($id, $expediente);

        $mensajeHito = $validadoPorAbogado
            ? sprintf('El abogado ha revocado la validación del documento «%s» y lo ha devuelto al cliente.', $doc->nombre())
            : sprintf('El abogado ha devuelto el documento «%s» al cliente.', $doc->nombre());

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'documento_requerimientos_devuelto',
            $mensajeHito,
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
            null,
            $documentoId,
        ));

        $this->realtime->publishContratacionUpdate($expedienteId, [
            'type' => 'documento_requerimientos_devuelto',
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
            if (EstadoFaseExpediente::RequerimientosListo !== $expediente->estadoFase()) {
                $this->expedienteRepository->save(
                    $expediente
                        ->withEstadoFase(EstadoFaseExpediente::RequerimientosListo)
                        ->touchEstadoCambio(),
                );
            }

            return;
        }

        if (EstadoFaseExpediente::RequerimientosListo === $expediente->estadoFase()) {
            $this->expedienteRepository->save(
                $expediente
                    ->withEstadoFase(EstadoFaseExpediente::RequerimientosEnProgreso)
                    ->touchEstadoCambio(),
            );
        }
    }
}
