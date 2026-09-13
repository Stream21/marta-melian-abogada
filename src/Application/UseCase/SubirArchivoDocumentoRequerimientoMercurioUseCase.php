<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\RequerimientoMercurioCompletitudService;
use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoRequerimientoDocumento;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\ResponsableRequerimientoDocumento;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoDocumentoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final class SubirArchivoDocumentoRequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteRequerimientoDocumentoRepositoryInterface $documentoRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private TramitacionSubfaseSyncService $subfaseSync,
        private RequerimientoMercurioCompletitudService $completitud,
    ) {
    }

    /**
     * @param array{content: string, filename: string} $file
     */
    public function __invoke(
        string $expedienteId,
        string $requerimientoId,
        string $documentoId,
        array $file,
        bool $desdePortalCliente = false,
    ): void {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de tramitación.');
        }

        $req = $this->requerimientoRepository->findById(new ExpedienteRequerimientoMercurioId($requerimientoId));
        if (null === $req || $req->expedienteId()->value() !== $id->value()) {
            throw new \InvalidArgumentException('Requerimiento no encontrado.');
        }
        if (!$req->estado()->estaAbierto()) {
            throw new \InvalidArgumentException('El requerimiento ya está cerrado.');
        }

        $doc = $this->documentoRepository->findById(new ExpedienteRequerimientoDocumentoId($documentoId));
        if (null === $doc || $doc->requerimientoId()->value() !== $requerimientoId) {
            throw new \InvalidArgumentException('Documento de requerimiento no encontrado.');
        }

        if ($desdePortalCliente) {
            if (ResponsableRequerimientoDocumento::Cliente !== $doc->responsable()) {
                throw new \InvalidArgumentException('Este documento no corresponde al cliente.');
            }
        } elseif (ResponsableRequerimientoDocumento::Abogado !== $doc->responsable()) {
            throw new \InvalidArgumentException('Este documento corresponde al cliente.');
        }

        if (!in_array($doc->estado(), [EstadoRequerimientoDocumento::Pendiente, EstadoRequerimientoDocumento::Rechazado], true)) {
            throw new \InvalidArgumentException('El documento no admite una nueva subida.');
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $file['filename']) ?: 'documento.pdf';
        $path = $this->fileStorage->savePdf(
            $id,
            'req-doc-' . substr($documentoId, 0, 8) . '-' . $safeName,
            $file['content'],
        );

        $actualizado = $doc->withArchivo($path);
        // Si lo adjunta el despacho, queda validado de inmediato (sin paso extra).
        if (!$desdePortalCliente) {
            $actualizado = $actualizado->validar();
        }
        $this->documentoRepository->save($actualizado);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            $desdePortalCliente
                ? 'requerimiento_mercurio_doc_subido'
                : 'requerimiento_mercurio_doc_validado',
            sprintf(
                $desdePortalCliente
                    ? 'Archivo «%s» del requerimiento «%s» (cliente).'
                    : 'Archivo «%s» adjuntado y validado en requerimiento «%s».',
                $doc->nombre(),
                $req->nombre(),
            ),
            $desdePortalCliente ? ActorHitoExpediente::Cliente : ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->completitud->sincronizarEstadoTrasCambioCliente($req);
        $this->subfaseSync->sync($expediente);
    }
}
