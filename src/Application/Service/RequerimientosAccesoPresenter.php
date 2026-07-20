<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteDocumentoArchivoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;

final class RequerimientosAccesoPresenter
{
    public function __construct(
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteDocumentoArchivoRepositoryInterface $archivoRepository,
        private RequerimientosDocumentoFlagsService $documentoFlags,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function present(Expediente $expediente): ?array
    {
        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            return null;
        }

        $entregas = [];
        foreach ($this->documentoEntregadoRepository->findByExpediente($expediente->id()) as $entrega) {
            $docId = $entrega->expedienteDocumentoRequeridoId();
            if (null !== $docId) {
                $entregas[$docId->value()] = $entrega;
            }
        }

        $entregadoIds = array_values(array_map(
            static fn ($entrega) => $entrega->id(),
            array_values($entregas),
        ));
        $archivosPorEntregadoId = $this->archivoRepository->findByEntregadoIds($entregadoIds);

        $documentos = [];
        foreach ($this->documentoRequeridoRepository->findByExpediente($expediente->id()) as $doc) {
            $entrega = $entregas[$doc->id()->value()] ?? null;
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

            $documentos[] = array_merge([
                'id' => $doc->id()->value(),
                'nombre' => $doc->nombre(),
                'descripcion' => $doc->descripcion(),
                'obligatorio' => $doc->obligatorio(),
                'tipo' => $doc->tipo()->value,
                'maxImagenes' => $doc->maxImagenes(),
            ], $this->documentoFlags->flags($doc, $entrega, $archivos));
        }

        $pendientes = array_filter(
            $documentos,
            fn (array $d) => in_array($d['estado'], [
                EstadoDocumentoEntregado::Pendiente->value,
                EstadoDocumentoEntregado::Rechazado->value,
            ], true),
        );

        $enRevision = array_filter(
            $documentos,
            fn (array $d) => EstadoDocumentoEntregado::Entregado->value === $d['estado'],
        );

        $resumen = $this->documentoFlags->resumenExpediente($documentos);

        return [
            'documentos' => $documentos,
            'pendientesSubida' => count($pendientes),
            'enRevision' => count($enRevision),
            'esperandoAbogado' => $resumen['esperandoAbogado'],
            'agenteResponsableExpediente' => $resumen['agenteResponsableExpediente'],
        ];
    }
}
