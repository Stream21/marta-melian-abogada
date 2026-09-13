<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\SubidoPorDocumento;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

/**
 * Subfase de documentación para listados: progreso documental + detalle para tooltip.
 */
final class DocumentacionSubfaseListadoService
{
    public function __construct(
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private RequerimientosProgresoCalculator $progresoCalculator,
    ) {
    }

    /**
     * @param list<string> $expedienteIds
     *
     * @return array<string, array{
     *     validados: int,
     *     total: int,
     *     pendientes: int,
     *     enRevision: int,
     *     label: string,
     *     items: list<array{
     *         nombre: string,
     *         obligatorio: bool,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }>
     */
    public function aggregate(array $expedienteIds): array
    {
        if ([] === $expedienteIds) {
            return [];
        }

        $requeridosByExp = $this->documentoRequeridoRepository->findByExpedienteIds($expedienteIds);
        $entregasByExp = $this->documentoEntregadoRepository->findByExpedienteIds($expedienteIds);

        $result = [];
        foreach ($expedienteIds as $expedienteId) {
            $docs = $requeridosByExp[$expedienteId] ?? [];
            $entregas = $entregasByExp[$expedienteId] ?? [];
            $result[$expedienteId] = $this->buildSubfase($docs, $entregas);
        }

        return $result;
    }

    /**
     * @return array{
     *     validados: int,
     *     total: int,
     *     pendientes: int,
     *     enRevision: int,
     *     label: string,
     *     items: list<array{
     *         nombre: string,
     *         obligatorio: bool,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }|null
     */
    public function paraExpediente(ExpedienteId $expedienteId): ?array
    {
        $docs = $this->documentoRequeridoRepository->findByExpediente($expedienteId);
        if ([] === $docs) {
            return [
                'validados' => 0,
                'total' => 0,
                'pendientes' => 0,
                'enRevision' => 0,
                'label' => 'Sin documentos',
                'items' => [],
            ];
        }

        $entregas = $this->documentoEntregadoRepository->findByExpediente($expedienteId);

        return $this->buildSubfase($docs, $entregas);
    }

    /**
     * @param ExpedienteDocumentoRequerido[] $docs
     * @param ExpedienteDocumentoEntregado[] $entregas
     *
     * @return array{
     *     validados: int,
     *     total: int,
     *     pendientes: int,
     *     enRevision: int,
     *     label: string,
     *     items: list<array{
     *         nombre: string,
     *         obligatorio: bool,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }
     */
    private function buildSubfase(array $docs, array $entregas): array
    {
        $entregasPorDocId = [];
        foreach ($entregas as $entrega) {
            $docReqId = $entrega->expedienteDocumentoRequeridoId();
            if (null !== $docReqId) {
                $entregasPorDocId[$docReqId->value()] = $entrega;
            }
        }

        $progreso = $this->progresoCalculator->calcular($docs, $entregasPorDocId);
        $pendientes = $progreso['pendientesEntrega'] + $progreso['rechazados'];
        $validados = $progreso['validados'];
        $total = $progreso['total'];

        $items = [];
        foreach ($docs as $doc) {
            $entrega = $entregasPorDocId[$doc->id()->value()] ?? null;
            $estado = $entrega?->estado() ?? EstadoDocumentoEntregado::Pendiente;
            $subidoPor = $entrega?->subidoPor() ?? SubidoPorDocumento::Cliente;
            $fecha = null;
            if (null !== $entrega && EstadoDocumentoEntregado::Pendiente !== $estado) {
                $fecha = $entrega->entregadoAt()->format(\DateTimeInterface::ATOM);
            }

            $items[] = [
                'nombre' => $doc->nombre(),
                'obligatorio' => $doc->obligatorio(),
                'estado' => $estado->value,
                'estadoLabel' => $this->progresoCalculator->estadoLabel(
                    $estado,
                    $subidoPor,
                    $entrega?->responsableActual(),
                ),
                'fecha' => $fecha,
            ];
        }

        $label = 0 === $total
            ? 'Sin documentos'
            : sprintf('%d/%d', $validados, $total);

        return [
            'validados' => $validados,
            'total' => $total,
            'pendientes' => $pendientes,
            'enRevision' => $progreso['enRevision'],
            'label' => $label,
            'items' => $items,
        ];
    }
}
