<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Domain\Entity\ActorResponsableDocumento;
use App\Domain\Entity\ExpedienteDocumentoArchivo;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\SubidoPorDocumento;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\ExpedienteDocumentoArchivoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class DocumentoEntregaArchivosService
{
    public function __construct(
        private DocumentoUploadNormalizer $uploadNormalizer,
        private ExpedienteFileStoragePort $fileStorage,
        private ExpedienteDocumentoArchivoRepositoryInterface $archivoRepository,
        private string $projectDir,
    ) {
    }

    /**
     * Convierte cada archivo a PDF por separado, persiste rutas y devuelve la entrega actualizada.
     *
     * @param list<array{content: string, mime: string, nombreOriginal?: string}> $archivos
     */
    public function persistirArchivosRequerimiento(
        ExpedienteId $expedienteId,
        ExpedienteDocumentoRequeridoId $docReqId,
        array $archivos,
        TipoDocumentoRequerido $tipo,
        int $maxImagenes,
        ?ExpedienteDocumentoEntregado $existente,
        EstadoDocumentoEntregado $estado,
        SubidoPorDocumento $subidoPor,
        bool $reemplazar = true,
    ): ExpedienteDocumentoEntregado {
        if (!$reemplazar && null !== $existente) {
            return $this->agregarArchivosRequerimiento(
                $expedienteId,
                $docReqId,
                $archivos,
                $tipo,
                $maxImagenes,
                $existente,
                $estado,
                $subidoPor,
            );
        }

        $pdfs = $this->uploadNormalizer->normalizarArchivosIndividuales($archivos, $tipo, $maxImagenes);
        $entregadoId = $existente?->id() ?? bin2hex(random_bytes(16));

        if (null !== $existente) {
            $this->eliminarArchivosEntrega($existente->id(), $existente->archivoPath());
        }

        $primaryPath = $this->guardarArchivosPdf($expedienteId, $docReqId, $entregadoId, $pdfs);

        if (null !== $existente) {
            return $this->aplicarEstadoEntrega($existente, $primaryPath, $estado, $subidoPor);
        }

        return new ExpedienteDocumentoEntregado(
            $entregadoId,
            $expedienteId,
            null,
            $docReqId,
            $primaryPath,
            $estado,
            new \DateTimeImmutable('now'),
            null,
            $subidoPor,
            EstadoDocumentoEntregado::Pendiente === $estado && SubidoPorDocumento::Abogado === $subidoPor
                ? ActorResponsableDocumento::Abogado
                : ActorResponsableDocumento::Cliente,
        );
    }

    /**
     * @param list<array{content: string, mime: string, nombreOriginal?: string}> $archivos
     */
    public function agregarArchivosRequerimiento(
        ExpedienteId $expedienteId,
        ExpedienteDocumentoRequeridoId $docReqId,
        array $archivos,
        TipoDocumentoRequerido $tipo,
        int $maxImagenes,
        ExpedienteDocumentoEntregado $existente,
        EstadoDocumentoEntregado $estado,
        SubidoPorDocumento $subidoPor,
    ): ExpedienteDocumentoEntregado {
        $existentes = $this->archivoRepository->findByEntregadoId($existente->id());
        $totalTrasSubida = count($existentes) + count($archivos);
        if ($totalTrasSubida > $maxImagenes) {
            throw new \InvalidArgumentException(
                sprintf('Este requisito admite como máximo %d archivo(s).', $maxImagenes),
            );
        }

        $pdfs = $this->uploadNormalizer->normalizarArchivosIndividuales($archivos, $tipo, $maxImagenes);
        $ordenInicial = [] === $existentes
            ? 0
            : max(array_map(static fn ($a) => $a->orden(), $existentes)) + 1;

        $primaryPath = '' !== trim($existente->archivoPath()) ? $existente->archivoPath() : '';
        $archivoEntities = [];

        foreach ($pdfs as $offset => $pdf) {
            $filename = sprintf(
                'requerimientos/doc_%s_%s_%d.pdf',
                $docReqId->value(),
                date('YmdHis'),
                $ordenInicial + $offset + 1,
            );
            $path = $this->fileStorage->savePdf($expedienteId, $filename, $pdf['content']);
            if ('' === $primaryPath) {
                $primaryPath = $path;
            }

            $archivoEntities[] = new ExpedienteDocumentoArchivo(
                bin2hex(random_bytes(16)),
                $existente->id(),
                $path,
                $pdf['nombreOriginal'],
                $ordenInicial + $offset,
                new \DateTimeImmutable('now'),
            );
        }

        $this->archivoRepository->saveMany($archivoEntities);

        return $this->aplicarEstadoEntrega($existente, $primaryPath, $estado, $subidoPor);
    }

    /**
     * @param list<array{content: string, nombreOriginal: string}> $pdfs
     */
    private function guardarArchivosPdf(
        ExpedienteId $expedienteId,
        ExpedienteDocumentoRequeridoId $docReqId,
        string $entregadoId,
        array $pdfs,
    ): string {
        $archivoEntities = [];
        $primaryPath = '';

        foreach ($pdfs as $index => $pdf) {
            $filename = sprintf(
                'requerimientos/doc_%s_%s_%d.pdf',
                $docReqId->value(),
                date('YmdHis'),
                $index + 1,
            );
            $path = $this->fileStorage->savePdf($expedienteId, $filename, $pdf['content']);
            if ('' === $primaryPath) {
                $primaryPath = $path;
            }

            $archivoEntities[] = new ExpedienteDocumentoArchivo(
                bin2hex(random_bytes(16)),
                $entregadoId,
                $path,
                $pdf['nombreOriginal'],
                $index,
                new \DateTimeImmutable('now'),
            );
        }

        $this->archivoRepository->saveMany($archivoEntities);

        return $primaryPath;
    }

    private function aplicarEstadoEntrega(
        ExpedienteDocumentoEntregado $existente,
        string $primaryPath,
        EstadoDocumentoEntregado $estado,
        SubidoPorDocumento $subidoPor,
    ): ExpedienteDocumentoEntregado {
        return match ($estado) {
            EstadoDocumentoEntregado::Validado => $existente->marcarValidadoPorAbogado($primaryPath),
            EstadoDocumentoEntregado::Entregado => $existente->marcarEntregado($primaryPath),
            EstadoDocumentoEntregado::Pendiente => $existente->marcarPendienteConArchivos($primaryPath, $subidoPor),
            default => throw new \InvalidArgumentException('Estado de entrega no soportado al actualizar.'),
        };
    }

    private function eliminarArchivosEntrega(string $entregadoId, string $legacyPath): void
    {
        foreach ($this->archivoRepository->findByEntregadoId($entregadoId) as $archivo) {
            $this->eliminarArchivoFisico($archivo->archivoPath());
        }

        if ('' !== trim($legacyPath)) {
            $this->eliminarArchivoFisico($legacyPath);
        }

        $this->archivoRepository->deleteByEntregadoId($entregadoId);
    }

    private function eliminarArchivoFisico(string $relativePath): void
    {
        $absolute = $this->projectDir . '/' . ltrim($relativePath, '/');
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}
