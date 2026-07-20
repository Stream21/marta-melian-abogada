<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class RequerimientosCompletitudValidator
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
    ) {
    }

    /**
     * @return array{
     *     total: int,
     *     obligatorios: int,
     *     validados: int,
     *     pendientesEntrega: int,
     *     enRevision: int,
     *     rechazados: int,
     *     todosObligatoriosValidados: bool,
     *     ningunoEnRevision: bool,
     *     requerimientosListo: bool
     * }
     */
    public function resumen(ExpedienteId $expedienteId): array
    {
        $documentos = $this->documentoRequeridoRepository->findByExpediente($expedienteId);
        $entregas = $this->indexEntregas($this->documentoEntregadoRepository->findByExpediente($expedienteId));

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

            $estado = $this->estadoDocumento($doc, $entregas);
            match ($estado) {
                EstadoDocumentoEntregado::Validado => (function () use (&$validados, &$obligatoriosValidados, $doc): void {
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

        $todosObligatoriosValidados = $obligatorios > 0
            ? $obligatoriosValidados === $obligatorios
            : true;
        $ningunoEnRevision = 0 === $enRevision;

        return [
            'total' => count($documentos),
            'obligatorios' => $obligatorios,
            'validados' => $validados,
            'pendientesEntrega' => $pendientesEntrega,
            'enRevision' => $enRevision,
            'rechazados' => $rechazados,
            'todosObligatoriosValidados' => $todosObligatoriosValidados,
            'ningunoEnRevision' => $ningunoEnRevision,
            'requerimientosListo' => $todosObligatoriosValidados && $ningunoEnRevision && 0 === $rechazados,
        ];
    }

    public function requerimientosListo(ExpedienteId $expedienteId): bool
    {
        return $this->resumen($expedienteId)['requerimientosListo'];
    }

    public function assertEnRequerimientos(ExpedienteId $expedienteId): void
    {
        $expediente = $this->expedienteRepository->findById($expedienteId);
        if (null === $expediente || FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de requerimientos.');
        }
    }

    /**
     * @param array<string, \App\Domain\Entity\ExpedienteDocumentoEntregado> $entregas
     */
    private function estadoDocumento(ExpedienteDocumentoRequerido $doc, array $entregas): EstadoDocumentoEntregado
    {
        $entrega = $entregas[$doc->id()->value()] ?? null;

        return $entrega?->estado() ?? EstadoDocumentoEntregado::Pendiente;
    }

    /**
     * @param \App\Domain\Entity\ExpedienteDocumentoEntregado[] $entregas
     *
     * @return array<string, \App\Domain\Entity\ExpedienteDocumentoEntregado>
     */
    private function indexEntregas(array $entregas): array
    {
        $index = [];
        foreach ($entregas as $entrega) {
            $docId = $entrega->expedienteDocumentoRequeridoId();
            if (null !== $docId) {
                $index[$docId->value()] = $entrega;
            }
        }

        return $index;
    }

    public function findDocumentoRequerido(
        ExpedienteId $expedienteId,
        ExpedienteDocumentoRequeridoId $documentoId,
    ): ExpedienteDocumentoRequerido {
        $doc = $this->documentoRequeridoRepository->findById($documentoId);
        if (null === $doc || !$doc->expedienteId()->equals($expedienteId)) {
            throw new \InvalidArgumentException('Documento requerido no encontrado.');
        }

        return $doc;
    }
}
