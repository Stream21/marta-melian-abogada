<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ActorResponsableDocumento;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\SubidoPorDocumento;

final class RequerimientosProgresoCalculator
{
    /**
     * @param ExpedienteDocumentoRequerido[] $documentos
     * @param array<string, ExpedienteDocumentoEntregado> $entregasPorDocId
     *
     * @return array{
     *   total: int,
     *   obligatorios: int,
     *   validados: int,
     *   pendientesEntrega: int,
     *   enRevision: int,
     *   rechazados: int,
     *   todosObligatoriosValidados: bool,
     *   ningunoEnRevision: bool,
     *   requerimientosListo: bool
     * }
     */
    public function calcular(array $documentos, array $entregasPorDocId): array
    {
        $total = count($documentos);
        $obligatorios = 0;
        $validados = 0;
        $pendientesEntrega = 0;
        $enRevision = 0;
        $rechazados = 0;
        $obligatoriosValidados = 0;

        foreach ($documentos as $doc) {
            if ($doc->obligatorio()) {
                ++$obligatorios;
            }

            $entrega = $entregasPorDocId[$doc->id()->value()] ?? null;
            $estado = $entrega?->estado() ?? EstadoDocumentoEntregado::Pendiente;

            match ($estado) {
                EstadoDocumentoEntregado::Validado => (function () use (&$validados, $doc, &$obligatoriosValidados): void {
                    ++$validados;
                    if ($doc->obligatorio()) {
                        ++$obligatoriosValidados;
                    }
                })(),
                EstadoDocumentoEntregado::Entregado => ++$enRevision,
                EstadoDocumentoEntregado::Rechazado => ++$rechazados,
                default => ++$pendientesEntrega,
            };
        }

        $todosObligatoriosValidados = $obligatorios > 0 && $obligatoriosValidados === $obligatorios;
        $ningunoEnRevision = 0 === $enRevision;

        return [
            'total' => $total,
            'obligatorios' => $obligatorios,
            'validados' => $validados,
            'pendientesEntrega' => $pendientesEntrega,
            'enRevision' => $enRevision,
            'rechazados' => $rechazados,
            'todosObligatoriosValidados' => $todosObligatoriosValidados,
            'ningunoEnRevision' => $ningunoEnRevision,
            'requerimientosListo' => $todosObligatoriosValidados && $ningunoEnRevision,
        ];
    }

    public function estadoLabel(
        EstadoDocumentoEntregado $estado,
        SubidoPorDocumento $subidoPor,
        ?ActorResponsableDocumento $responsableActual = null,
    ): string {
        if (
            EstadoDocumentoEntregado::Pendiente === $estado
            && ActorResponsableDocumento::Abogado === $responsableActual
        ) {
            return 'A cargo del abogado';
        }

        return match ($estado) {
            EstadoDocumentoEntregado::Validado => SubidoPorDocumento::Abogado === $subidoPor
                ? 'Aportado por el abogado'
                : 'Validado',
            EstadoDocumentoEntregado::Entregado => 'Pendiente de revisión',
            EstadoDocumentoEntregado::Rechazado => 'Devuelto al cliente',
            default => 'Pendiente de entrega',
        };
    }
}
