<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\EstadoRequerimientoMercurio;
use App\Domain\Entity\ExpedientePresentacionTelematica;
use App\Domain\Entity\ExpedienteRequerimientoMercurio;
use App\Domain\Repository\ExpedientePresentacionTelematicaRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

/**
 * Detalle de tramitación para listados: presentación inicial + requerimientos presentados (tooltip).
 */
final class TramitacionSubfaseListadoService
{
    public function __construct(
        private ExpedientePresentacionTelematicaRepositoryInterface $presentacionRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
    ) {
    }

    /**
     * @param list<string> $expedienteIds
     *
     * @return array<string, array{
     *     fechaPresentacion: string|null,
     *     items: list<array{
     *         nombre: string,
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

        $presentaciones = $this->presentacionRepository->findByExpedienteIds($expedienteIds);
        $requerimientos = $this->requerimientoRepository->findByExpedienteIds($expedienteIds);
        $result = [];
        foreach ($expedienteIds as $expedienteId) {
            $result[$expedienteId] = $this->buildDetalle(
                $presentaciones[$expedienteId] ?? null,
                $requerimientos[$expedienteId] ?? [],
            );
        }

        return $result;
    }

    /**
     * @return array{
     *     fechaPresentacion: string|null,
     *     items: list<array{
     *         nombre: string,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }|null
     */
    public function paraExpediente(ExpedienteId $expedienteId): ?array
    {
        $presentacion = $this->presentacionRepository->findByExpediente($expedienteId);
        $requerimientos = $this->requerimientoRepository->findByExpediente($expedienteId);

        return $this->buildDetalle($presentacion, $requerimientos);
    }

    /**
     * @param list<ExpedienteRequerimientoMercurio> $requerimientos
     *
     * @return array{
     *     fechaPresentacion: string|null,
     *     items: list<array{
     *         nombre: string,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }
     */
    private function buildDetalle(?ExpedientePresentacionTelematica $presentacion, array $requerimientos): array
    {
        $items = [];
        $fechaPrincipal = null;

        if (null !== $presentacion) {
            $fecha = $presentacion->fechaPresentacion()->format('Y-m-d');
            $fechaPrincipal = $fecha;
            $items[] = [
                'nombre' => 'Presentación inicial',
                'estado' => 'registrada',
                'estadoLabel' => 'Documento subido',
                'fecha' => $fecha,
            ];
            $items[] = [
                'nombre' => 'Justificante inicial',
                'estado' => 'adjunto',
                'estadoLabel' => 'Justificante de Mercurio',
                'fecha' => $fecha,
            ];
        }

        foreach ($requerimientos as $req) {
            if ($req->estado()->estaAbierto()) {
                continue;
            }

            $fecha = $req->updatedAt()->format('Y-m-d');
            if (null === $fechaPrincipal) {
                $fechaPrincipal = $fecha;
            }

            $nombre = trim($req->nombre());
            if ('' === $nombre) {
                $nombre = 'Requerimiento';
            }

            if (null !== $req->archivoPath() && '' !== $req->archivoPath()) {
                $items[] = [
                    'nombre' => $nombre.' · Presentación',
                    'estado' => EstadoRequerimientoMercurio::Presentado->value,
                    'estadoLabel' => 'Presentado',
                    'fecha' => $fecha,
                ];
            }

            if (null !== $req->justificantePresentacionPath() && '' !== $req->justificantePresentacionPath()) {
                $items[] = [
                    'nombre' => $nombre.' · Justificante',
                    'estado' => EstadoRequerimientoMercurio::Presentado->value,
                    'estadoLabel' => 'Justificante de Mercurio',
                    'fecha' => $fecha,
                ];
            }

            // Escrito presentado solo con justificante (sin PDF de presentación propio).
            if (
                (null === $req->archivoPath() || '' === $req->archivoPath())
                && (null === $req->justificantePresentacionPath() || '' === $req->justificantePresentacionPath())
            ) {
                $items[] = [
                    'nombre' => $nombre,
                    'estado' => EstadoRequerimientoMercurio::Presentado->value,
                    'estadoLabel' => 'Presentado',
                    'fecha' => $fecha,
                ];
            }
        }

        return [
            'fechaPresentacion' => $fechaPrincipal,
            'items' => $items,
        ];
    }
}
