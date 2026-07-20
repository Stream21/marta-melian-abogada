<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Port\DocumentToPdfConverterPort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\RequerimientosCompletitudValidator;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class SubirDocumentoRequerimientosUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private RequerimientosCompletitudValidator $completitudValidator,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private DocumentToPdfConverterPort $pdfConverter,
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

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('Este expediente no está en fase de requerimientos.');
        }

        $expedienteId = $expediente->id();
        $docId = new ExpedienteDocumentoRequeridoId($documentoRequeridoId);
        $doc = $this->completitudValidator->findDocumentoRequerido($expedienteId, $docId);

        $content = $this->normalizeToPdf($fileBinary, $mimeType);
        $filename = sprintf('requerimientos/doc_%s_%s.pdf', $documentoRequeridoId, date('YmdHis'));
        $path = $this->fileStorage->savePdf($expedienteId, $filename, $content);

        $existente = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento($expedienteId, $docId);

        if (null !== $existente) {
            $this->documentoEntregadoRepository->save($existente->marcarEntregado($path));
        } else {
            $this->documentoEntregadoRepository->save(new ExpedienteDocumentoEntregado(
                bin2hex(random_bytes(16)),
                $expedienteId,
                null,
                $docId,
                $path,
                EstadoDocumentoEntregado::Entregado,
                new \DateTimeImmutable('now'),
            ));
        }

        if (EstadoFaseExpediente::RequerimientosListo === $expediente->estadoFase()) {
            $this->expedienteRepository->save(
                $expediente->withEstadoFase(EstadoFaseExpediente::RequerimientosEnProgreso)->touchEstadoCambio(),
            );
        }

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $expedienteId,
            'documento_requerimientos_subido',
            sprintf('El cliente ha subido el documento «%s».', $doc->nombre()),
            ActorHitoExpediente::Cliente,
            new \DateTimeImmutable('now'),
        ));

        $this->realtime->publishContratacionUpdate($expedienteId->value(), [
            'type' => 'documento_requerimientos_subido',
            'documentoId' => $documentoRequeridoId,
            'documentoNombre' => $doc->nombre(),
            'actor' => 'cliente',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }

    private function normalizeToPdf(string $binary, string $mimeType): string
    {
        if (str_contains(strtolower($mimeType), 'pdf') || str_starts_with($binary, '%PDF')) {
            return $binary;
        }

        $temp = sys_get_temp_dir() . '/req-upload-' . bin2hex(random_bytes(8));
        file_put_contents($temp, $binary);
        try {
            $relative = $this->pdfConverter->convertToPdf($temp, $mimeType);

            return (string) file_get_contents($this->projectDir . '/' . ltrim($relative, '/'));
        } finally {
            @unlink($temp);
        }
    }
}
