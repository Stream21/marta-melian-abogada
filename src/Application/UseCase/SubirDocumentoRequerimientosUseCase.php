<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\DocumentoEntregaArchivosService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ActorResponsableDocumento;
use App\Domain\Entity\EstadoDocumentoEntregado;
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

final class SubirDocumentoRequerimientosUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteDocumentoArchivoRepositoryInterface $archivoRepository,
        private DocumentoEntregaArchivosService $entregaArchivosService,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    /**
     * @param list<array{content: string, mime: string, nombreOriginal?: string}> $archivos
     */
    public function __invoke(string $token, string $documentoId, array $archivos): void
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('Este expediente no está en fase de requerimientos.');
        }

        $docReqId = new ExpedienteDocumentoRequeridoId($documentoId);
        $doc = $this->documentoRequeridoRepository->findById($docReqId);
        if (null === $doc || !$doc->expedienteId()->equals($expediente->id())) {
            throw new \InvalidArgumentException('Documento requerido no encontrado.');
        }

        $existente = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento(
            $expediente->id(),
            $docReqId,
        );

        if (null !== $existente) {
            if (ActorResponsableDocumento::Cliente !== $existente->responsableActual()) {
                throw new \InvalidArgumentException('Su abogado está gestionando este documento.');
            }

            if (!in_array($existente->estado(), [
                EstadoDocumentoEntregado::Pendiente,
                EstadoDocumentoEntregado::Rechazado,
            ], true)) {
                throw new \InvalidArgumentException('Este documento ya fue entregado y está en revisión o validado.');
            }
        }

        $tieneArchivosParciales = null !== $existente && (
            [] !== $this->archivoRepository->findByEntregadoId($existente->id())
            || '' !== trim($existente->archivoPath())
        );

        $reemplazar = !$tieneArchivosParciales || TipoDocumentoRequerido::Individual === $doc->tipo();

        $entrega = $this->entregaArchivosService->persistirArchivosRequerimiento(
            $expediente->id(),
            $docReqId,
            $archivos,
            $doc->tipo(),
            $doc->maxImagenes(),
            $existente,
            EstadoDocumentoEntregado::Entregado,
            SubidoPorDocumento::Cliente,
            $reemplazar,
        );

        $this->documentoEntregadoRepository->save($entrega);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $expediente->id(),
            'documento_requerimientos_subido',
            sprintf('El cliente ha subido el documento «%s».', $doc->nombre()),
            ActorHitoExpediente::Cliente,
            new \DateTimeImmutable('now'),
            null,
            $documentoId,
        ));

        $this->realtime->publishContratacionUpdate($expediente->id()->value(), [
            'type' => 'documento_requerimientos_subido',
            'documentoId' => $documentoId,
            'documentoNombre' => $doc->nombre(),
            'actor' => 'cliente',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }
}
