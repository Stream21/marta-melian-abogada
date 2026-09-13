<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Expediente;

/**
 * Resumen de cobros/cuotas para listados: progreso + detalle para tooltip.
 */
final class CobrosResumenListadoService
{
    public function __construct(
        private CalendarioCobrosService $calendarioCobrosService,
    ) {
    }

    /**
     * @param Expediente[] $expedientes
     *
     * @return array<string, array{
     *     pagadas: int,
     *     total: int,
     *     vencidas: int,
     *     label: string,
     *     cobrado: float,
     *     importeTotal: float,
     *     pendiente: float,
     *     items: list<array{
     *         nombre: string,
     *         importe: float,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }>
     */
    public function aggregate(array $expedientes): array
    {
        $result = [];
        foreach ($expedientes as $expediente) {
            $result[$expediente->id()->value()] = $this->paraExpediente($expediente);
        }

        return $result;
    }

    /**
     * @return array{
     *     pagadas: int,
     *     total: int,
     *     vencidas: int,
     *     label: string,
     *     cobrado: float,
     *     importeTotal: float,
     *     pendiente: float,
     *     items: list<array{
     *         nombre: string,
     *         importe: float,
     *         estado: string,
     *         estadoLabel: string,
     *         fecha: string|null
     *     }>
     * }
     */
    public function paraExpediente(Expediente $expediente): array
    {
        // Sin payments: el calendario persistido ya refleja cuotas cobradas; vencidos se calculan por fecha.
        $cobros = $this->calendarioCobrosService->listarCobros($expediente, []);
        $resumen = $this->calendarioCobrosService->resumen($cobros);

        $pagadas = 0;
        $vencidas = 0;
        $items = [];

        foreach ($cobros as $cobro) {
            $estado = (string) ($cobro['estado'] ?? 'pendiente');
            if ('pagado' === $estado) {
                ++$pagadas;
            }
            if ('vencido' === $estado) {
                ++$vencidas;
            }

            $numero = (int) ($cobro['numero'] ?? 0);
            $items[] = [
                'nombre' => 1 === $numero ? 'Cuota 1 — Pago inicial' : sprintf('Cuota %d', $numero),
                'importe' => (float) ($cobro['importe'] ?? 0),
                'estado' => $estado,
                'estadoLabel' => (string) ($cobro['estadoLabel'] ?? 'Pendiente'),
                'fecha' => '' !== ($cobro['fechaVencimiento'] ?? '')
                    ? (string) $cobro['fechaVencimiento']
                    : null,
            ];
        }

        $total = count($cobros);
        $label = 0 === $total
            ? 'Sin cuotas'
            : sprintf('%d/%d', $pagadas, $total);

        return [
            'pagadas' => $pagadas,
            'total' => $total,
            'vencidas' => $vencidas,
            'label' => $label,
            'cobrado' => $resumen['cobrado'],
            'importeTotal' => $resumen['total'],
            'pendiente' => $resumen['pendiente'],
            'items' => $items,
        ];
    }
}
