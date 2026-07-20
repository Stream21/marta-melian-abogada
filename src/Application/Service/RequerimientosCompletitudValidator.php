<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class RequerimientosCompletitudValidator
{
    public function __construct(
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function resumen(ExpedienteId $expedienteId): array
    {
        $entregas = [];
        foreach ($this->documentoEntregadoRepository->findByExpediente($expedienteId) as $entrega) {
            $docReqId = $entrega->expedienteDocumentoRequeridoId();
            if (null !== $docReqId) {
                $entregas[$docReqId->value()] = $entrega;
            }
        }

        $documentos = $this->documentoRequeridoRepository->findByExpediente($expedienteId);
        $total = count($documentos);
        $obligatorios = 0;
        $validados = 0;
        $pendientesEntrega = 0;
        $enRevision = 0;
        $rechazados = 0;

        foreach ($documentos as $doc) {
            if (!$doc->obligatorio()) {
                continue;
            }
            ++$obligatorios;
            $estado = ($entregas[$doc->id()->value()] ?? null)?->estado()->value
                ?? EstadoDocumentoEntregado::Pendiente->value;
            match ($estado) {
                EstadoDocumentoEntregado::Validado->value => ++$validados,
                EstadoDocumentoEntregado::Entregado->value => ++$enRevision,
                EstadoDocumentoEntregado::Rechazado->value => ++$rechazados,
                default => ++$pendientesEntrega,
            };
        }

        foreach ($documentos as $doc) {
            if ($doc->obligatorio()) {
                continue;
            }
            $estado = ($entregas[$doc->id()->value()] ?? null)?->estado()->value
                ?? EstadoDocumentoEntregado::Pendiente->value;
            if (EstadoDocumentoEntregado::Entregado->value === $estado) {
                ++$enRevision;
            }
        }

        $todosObligatoriosValidados = $obligatorios > 0 && $validados === $obligatorios;
        $ningunoEnRevision = 0 === $enRevision;
        $requerimientosListo = $todosObligatoriosValidados && $ningunoEnRevision;

        $expediente = $this->expedienteRepository->findById($expedienteId);
        if ($requerimientosListo && null !== $expediente
            && EstadoFaseExpediente::RequerimientosListo !== $expediente->estadoFase()) {
            $this->expedienteRepository->save(
                $expediente->withEstadoFase(EstadoFaseExpediente::RequerimientosListo)->touchEstadoCambio(),
            );
        }

        return [
            'total' => $total,
            'obligatorios' => $obligatorios,
            'validados' => $validados,
            'pendientesEntrega' => $pendientesEntrega,
            'enRevision' => $enRevision,
            'rechazados' => $rechazados,
            'todosObligatoriosValidados' => $todosObligatoriosValidados,
            'ningunoEnRevision' => $ningunoEnRevision,
            'requerimientosListo' => $requerimientosListo,
        ];
    }
}
