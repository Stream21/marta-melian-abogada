<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\DocumentoEntregaArchivosService;
use App\Application\Service\RequerimientosProgresoCalculator;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ActorResponsableDocumento;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\SubidoPorDocumento;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoArchivoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class SubirDocumentoRequerimientosAbogadoUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteDocumentoArchivoRepositoryInterface $archivoRepository,
        private DocumentoEntregaArchivosService $entregaArchivosService,
        private ContratacionRepositoryInterface $contratacionRepository,
        private RequerimientosProgresoCalculator $progresoCalculator,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    /**
     * @param list<array{content: string, mime: string, nombreOriginal?: string}> $archivos
     */
    public function __invoke(string $expedienteId, string $documentoId, array $archivos, string $modo = 'validar'): void
    {
        $modo = 'aportar' === $modo ? 'aportar' : 'validar';

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

        $existente = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento($id, $docReqId);
        if (null !== $existente) {
            if (EstadoDocumentoEntregado::Entregado === $existente->estado()) {
                throw new \InvalidArgumentException(
                    'Hay un documento del cliente pendiente de revisión. Valídelo o devuélvalo antes de adjuntar otro.',
                );
            }

            if (
                EstadoDocumentoEntregado::Validado === $existente->estado()
                && SubidoPorDocumento::Cliente === $existente->subidoPor()
            ) {
                throw new \InvalidArgumentException(
                    'Este documento ya fue validado. Devuélvalo al cliente si necesita corregirlo.',
                );
            }

            if (
                EstadoDocumentoEntregado::Pendiente === $existente->estado()
                && ActorResponsableDocumento::Abogado !== $existente->responsableActual()
            ) {
                throw new \InvalidArgumentException(
                    'Debe tomar el requisito antes de adjuntar archivos o derivarlo al cliente.',
                );
            }

            if (
                EstadoDocumentoEntregado::Rechazado === $existente->estado()
                && ActorResponsableDocumento::Abogado !== $existente->responsableActual()
            ) {
                throw new \InvalidArgumentException('Este documento está pendiente del cliente.');
            }
        }

        $estadoDestino = 'aportar' === $modo
            ? EstadoDocumentoEntregado::Pendiente
            : EstadoDocumentoEntregado::Validado;

        $reemplazar = true;
        if ('aportar' === $modo && null !== $existente) {
            $tieneArchivos = [] !== $this->archivoRepository->findByEntregadoId($existente->id())
                || '' !== trim($existente->archivoPath());
            $reemplazar = !$tieneArchivos || TipoDocumentoRequerido::Individual === $doc->tipo();
        }

        $entrega = $this->entregaArchivosService->persistirArchivosRequerimiento(
            $id,
            $docReqId,
            $archivos,
            $doc->tipo(),
            $doc->maxImagenes(),
            $existente,
            $estadoDestino,
            SubidoPorDocumento::Abogado,
            $reemplazar,
        );

        $this->documentoEntregadoRepository->save($entrega);

        $descripcion = 'aportar' === $modo
            ? sprintf('El abogado ha aportado archivos al documento «%s» (pendiente de derivar al cliente).', $doc->nombre())
            : sprintf('El abogado ha aportado el documento «%s».', $doc->nombre());

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'documento_requerimientos_subido',
            $descripcion,
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->sincronizarEstadoExpediente($id, $expediente);

        $this->realtime->publishContratacionUpdate($expedienteId, [
            'type' => 'documento_requerimientos_subido',
            'documentoId' => $documentoId,
            'documentoNombre' => $doc->nombre(),
            'actor' => 'abogado',
            'modo' => $modo,
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
        } elseif (EstadoFaseExpediente::RequerimientosListo === $expediente->estadoFase()) {
            $this->expedienteRepository->save(
                $expediente
                    ->withEstadoFase(EstadoFaseExpediente::RequerimientosEnProgreso)
                    ->touchEstadoCambio(),
            );
        }
    }
}
