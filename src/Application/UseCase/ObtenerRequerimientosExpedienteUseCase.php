<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\RequerimientosDocumentoFlagsService;
use App\Application\Service\RequerimientosProgresoCalculator;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteDocumentoArchivoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ObtenerRequerimientosExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteDocumentoArchivoRepositoryInterface $archivoRepository,
        private RequerimientosProgresoCalculator $progresoCalculator,
        private RequerimientosDocumentoFlagsService $documentoFlags,
        private InicializarRequerimientosUseCase $inicializarRequerimientos,
        private string $frontendBaseUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $expedienteId): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de requerimientos.');
        }

        ($this->inicializarRequerimientos)($id);

        $entregasPorDocId = [];
        foreach ($this->documentoEntregadoRepository->findByExpediente($id) as $entrega) {
            $docId = $entrega->expedienteDocumentoRequeridoId();
            if (null !== $docId) {
                $entregasPorDocId[$docId->value()] = $entrega;
            }
        }

        $documentosDb = $this->documentoRequeridoRepository->findByExpediente($id);
        $entregadoIds = array_values(array_map(
            static fn (ExpedienteDocumentoEntregado $entrega): string => $entrega->id(),
            array_filter(array_values($entregasPorDocId)),
        ));
        $archivosPorEntregadoId = $this->archivoRepository->findByEntregadoIds($entregadoIds);

        $documentos = array_map(
            fn ($doc) => $this->serializarDocumento(
                $doc,
                $entregasPorDocId[$doc->id()->value()] ?? null,
                $archivosPorEntregadoId,
            ),
            $documentosDb,
        );

        $progreso = $this->progresoCalculator->calcular($documentosDb, $entregasPorDocId);
        $resumen = $this->documentoFlags->resumenExpediente($documentos);

        $accessUrl = null;
        if (null !== $expediente->accessToken()) {
            $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        }

        return [
            'expedienteId' => $expediente->id()->value(),
            'numero' => $expediente->numero(),
            'faseNegocio' => $expediente->faseNegocio()->value,
            'estadoFase' => $expediente->estadoFase()->value,
            'estadoFaseLabel' => $expediente->estadoFase()->label(),
            'accessUrl' => $accessUrl,
            'documentos' => $documentos,
            'escritos' => [],
            'progreso' => $progreso,
            'puedeAvanzarFase3' => $progreso['requerimientosListo'],
            'esperandoAbogado' => $resumen['esperandoAbogado'],
            'agenteResponsableExpediente' => $resumen['agenteResponsableExpediente'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarDocumento(
        \App\Domain\Entity\ExpedienteDocumentoRequerido $doc,
        ?ExpedienteDocumentoEntregado $entrega,
        array $archivosPorEntregadoId,
    ): array {
        $archivos = [];

        if (null !== $entrega) {
            foreach ($archivosPorEntregadoId[$entrega->id()] ?? [] as $archivo) {
                $archivos[] = [
                    'id' => $archivo->id(),
                    'nombre' => $archivo->nombreOriginal(),
                    'orden' => $archivo->orden(),
                ];
            }
        }

        return array_merge([
            'id' => $doc->id()->value(),
            'nombre' => $doc->nombre(),
            'descripcion' => $doc->descripcion(),
            'obligatorio' => $doc->obligatorio(),
            'tipo' => $doc->tipo()->value,
            'maxImagenes' => $doc->maxImagenes(),
            'orden' => $doc->orden(),
            'origen' => $doc->origen()->value,
            'origenLabel' => $doc->origen()->label(),
        ], $this->documentoFlags->flags($doc, $entrega, $archivos));
    }
}
