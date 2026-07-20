<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\DocumentToPdfConverterPort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteDocumentoArchivoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class GenerarPdfConjuntoRequerimientosUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteDocumentoArchivoRepositoryInterface $archivoRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private DocumentToPdfConverterPort $pdfConverter,
        private string $projectDir,
    ) {
    }

    /**
     * Genera un único PDF uniendo los archivos indicados (orden explícito) o, en su defecto,
     * todos los PDF de cada requisito validado en el orden de $documentoIds.
     *
     * @param list<string> $documentoIds
     * @param list<string> $archivoIds
     *
     * @return array{content: string, filename: string}
     */
    public function __invoke(string $expedienteId, array $documentoIds = [], array $archivoIds = []): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de requerimientos.');
        }

        if ([] !== $archivoIds) {
            $sources = $this->resolverFuentesPorArchivoIds($id, $archivoIds);
        } elseif ([] !== $documentoIds) {
            $sources = $this->resolverFuentesPorDocumentoIds($id, $documentoIds);
        } else {
            throw new \InvalidArgumentException('Debe seleccionar al menos un archivo.');
        }

        $mergedRelative = $this->pdfConverter->mergeSourcesToPdf($sources);

        try {
            $content = $this->fileStorage->readRelativePath($mergedRelative);
        } finally {
            $absoluteMerged = $this->projectDir . '/' . ltrim($mergedRelative, '/');
            if (is_file($absoluteMerged)) {
                @unlink($absoluteMerged);
            }
        }

        $numeroSeguro = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $expediente->numero()) ?? 'expediente';
        $filename = sprintf('mercurio-%s-%s.pdf', $numeroSeguro, date('YmdHis'));

        return [
            'content' => $content,
            'filename' => $filename,
        ];
    }

    /**
     * @param list<string> $archivoIds
     *
     * @return list<array{path: string, mime: string}>
     */
    private function resolverFuentesPorArchivoIds(ExpedienteId $expedienteId, array $archivoIds): array
    {
        $entregasPorId = [];
        $docNombrePorEntregadoId = [];

        foreach ($this->documentoEntregadoRepository->findByExpediente($expedienteId) as $entrega) {
            $entregasPorId[$entrega->id()] = $entrega;
            $docReqId = $entrega->expedienteDocumentoRequeridoId();
            if (null !== $docReqId) {
                $doc = $this->documentoRequeridoRepository->findById($docReqId);
                $docNombrePorEntregadoId[$entrega->id()] = $doc?->nombre() ?? 'Documento';
            }
        }

        $sources = [];
        foreach ($archivoIds as $archivoId) {
            if (str_starts_with($archivoId, 'doc:')) {
                $documentoId = substr($archivoId, 4);
                if ('' === $documentoId) {
                    throw new \InvalidArgumentException('Identificador de documento inválido.');
                }
                foreach ($this->fuentesDeDocumentoRequerido($expedienteId, $documentoId) as $source) {
                    $sources[] = $source;
                }
                continue;
            }

            $archivo = $this->archivoRepository->findById($archivoId);
            if (null === $archivo) {
                throw new \InvalidArgumentException(sprintf('Archivo no encontrado: %s.', $archivoId));
            }

            $entrega = $entregasPorId[$archivo->entregadoId()] ?? null;
            if (null === $entrega || !$entrega->expedienteId()->equals($expedienteId)) {
                throw new \InvalidArgumentException('El archivo no pertenece a este expediente.');
            }

            if (EstadoDocumentoEntregado::Validado !== $entrega->estado()) {
                $nombre = $docNombrePorEntregadoId[$entrega->id()] ?? 'Documento';
                throw new \InvalidArgumentException(
                    sprintf('«%s» no está validado y no puede incluirse en el PDF conjunto.', $nombre),
                );
            }

            $sources[] = [
                'path' => $this->fileStorage->getAbsolutePath($archivo->archivoPath()),
                'mime' => 'application/pdf',
            ];
        }

        return $sources;
    }

    /**
     * @return list<array{path: string, mime: string}>
     */
    private function fuentesDeDocumentoRequerido(ExpedienteId $expedienteId, string $documentoId): array
    {
        $docReqId = new ExpedienteDocumentoRequeridoId($documentoId);
        $doc = $this->documentoRequeridoRepository->findById($docReqId);
        if (null === $doc || !$doc->expedienteId()->equals($expedienteId)) {
            throw new \InvalidArgumentException(sprintf('Documento requerido no encontrado: %s.', $documentoId));
        }

        $entrega = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento($expedienteId, $docReqId);
        if (null === $entrega) {
            throw new \InvalidArgumentException(sprintf('«%s» no tiene archivo adjunto.', $doc->nombre()));
        }

        if (EstadoDocumentoEntregado::Validado !== $entrega->estado()) {
            throw new \InvalidArgumentException(
                sprintf('«%s» no está validado y no puede incluirse en el PDF conjunto.', $doc->nombre()),
            );
        }

        $archivos = $this->archivoRepository->findByEntregadoId($entrega->id());
        if ([] === $archivos) {
            $archivoPath = $entrega->archivoPath();
            if ('' === trim($archivoPath)) {
                throw new \InvalidArgumentException(sprintf('«%s» no tiene archivo adjunto.', $doc->nombre()));
            }

            return [[
                'path' => $this->fileStorage->getAbsolutePath($archivoPath),
                'mime' => 'application/pdf',
            ]];
        }

        $sources = [];
        foreach ($archivos as $archivo) {
            $sources[] = [
                'path' => $this->fileStorage->getAbsolutePath($archivo->archivoPath()),
                'mime' => 'application/pdf',
            ];
        }

        return $sources;
    }

    /**
     * @param list<string> $documentoIds
     *
     * @return list<array{path: string, mime: string}>
     */
    private function resolverFuentesPorDocumentoIds(ExpedienteId $expedienteId, array $documentoIds): array
    {
        $sources = [];

        foreach ($documentoIds as $documentoId) {
            foreach ($this->fuentesDeDocumentoRequerido($expedienteId, $documentoId) as $source) {
                $sources[] = $source;
            }
        }

        return $sources;
    }
}
