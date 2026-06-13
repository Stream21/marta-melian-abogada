<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Port\DocumentToPdfConverterPort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\ContratacionCompletitudValidator;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;
use App\Domain\ValueObject\TramiteId;

final class SubirDocumentoContratacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private TramiteDocumentoRequeridoRepositoryInterface $documentoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private DocumentToPdfConverterPort $pdfConverter,
        private ContratacionCompletitudValidator $completitudValidator,
        private ContratacionRealtimePort $realtime,
        private string $projectDir,
    ) {
    }

    public function __invoke(string $token, string $documentoRequeridoId, string $fileBinary, string $mimeType): void
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('Este expediente ya no está en fase de contratación.');
        }

        $pasoActivo = $this->completitudValidator->pasoActivoCliente($expediente->id());
        if (PasoContratacionCliente::DatosCliente !== $pasoActivo) {
            throw new \InvalidArgumentException('Los documentos adicionales solo pueden subirse durante el paso de identidad y datos.');
        }

        $docId = new TramiteDocumentoRequeridoId($documentoRequeridoId);
        $doc = $this->findDocumento($expediente->tramiteId(), $docId);
        if (null === $doc) {
            throw new \InvalidArgumentException('Documento requerido no encontrado.');
        }

        $content = $this->normalizeToPdf($fileBinary, $mimeType);
        $filename = sprintf('docs/doc_%s_%s.pdf', $documentoRequeridoId, date('YmdHis'));
        $path = $this->fileStorage->savePdf($expediente->id(), $filename, $content);

        $this->documentoEntregadoRepository->save(new ExpedienteDocumentoEntregado(
            bin2hex(random_bytes(16)),
            $expediente->id(),
            $docId,
            $path,
            EstadoDocumentoEntregado::Entregado,
            new \DateTimeImmutable('now'),
        ));

        $this->realtime->publishContratacionUpdate($expediente->id()->value(), [
            'type' => 'documento_subido',
            'documentoId' => $documentoRequeridoId,
            'actor' => 'cliente',
        ]);
    }

    private function findDocumento(?string $tramiteId, TramiteDocumentoRequeridoId $docId): ?\App\Domain\Entity\TramiteDocumentoRequerido
    {
        if (null === $tramiteId || '' === $tramiteId) {
            return null;
        }

        foreach ($this->documentoRepository->findByTramiteId(new TramiteId($tramiteId)) as $doc) {
            if ($doc->id()->value() === $docId->value()) {
                return $doc;
            }
        }

        return null;
    }

    private function normalizeToPdf(string $binary, string $mimeType): string
    {
        if (str_contains(strtolower($mimeType), 'pdf') || str_starts_with($binary, '%PDF')) {
            return $binary;
        }

        $temp = sys_get_temp_dir() . '/doc-upload-' . bin2hex(random_bytes(8));
        file_put_contents($temp, $binary);
        try {
            $relative = $this->pdfConverter->convertToPdf($temp, $mimeType);

            return (string) file_get_contents($this->projectDir . '/' . ltrim($relative, '/'));
        } finally {
            @unlink($temp);
        }
    }
}
