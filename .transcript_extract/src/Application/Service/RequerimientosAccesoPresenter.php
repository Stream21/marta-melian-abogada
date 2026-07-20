<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;

final class RequerimientosAccesoPresenter
{
    public function __construct(
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
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

        $documentos = [];
        foreach ($this->documentoRequeridoRepository->findByExpediente($expediente->id()) as $doc) {
            $entrega = $entregas[$doc->id()->value()] ?? null;
            $estado = $entrega?->estado()->value ?? EstadoDocumentoEntregado::Pendiente->value;
            $documentos[] = [
                'id' => $doc->id()->value(),
                'nombre' => $doc->nombre(),
                'descripcion' => $doc->descripcion(),
                'obligatorio' => $doc->obligatorio(),
                'tipo' => $doc->tipo()->value,
                'maxImagenes' => $doc->maxImagenes(),
                'estado' => $estado,
                'notaRechazo' => $entrega?->notaRechazo(),
                'entregadoAt' => $entrega?->entregadoAt()->format(\DateTimeInterface::ATOM),
                'puedeSubir' => in_array($estado, [
                    EstadoDocumentoEntregado::Pendiente->value,
                    EstadoDocumentoEntregado::Rechazado->value,
                ], true),
            ];
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

        return [
            'documentos' => $documentos,
            'pendientesSubida' => count($pendientes),
            'enRevision' => count($enRevision),
            'esperandoAbogado' => 0 === count($pendientes) && count($enRevision) > 0,
        ];
    }
}
