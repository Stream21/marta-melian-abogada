<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PlanPagoExpediente;

final class CalendarioPagoService
{
    private const int MARGEN_DIAS_PRIMERA_CUOTA = 2;

    /**
     * @return list<array{numero: int, importe: float, fechaVencimiento: string, estado: string}>
     */
    public function calcular(
        float $honorariosTotal,
        PlanPagoExpediente $planPago,
        int $numCuotas,
        \DateTimeImmutable $fechaFirmaContrato,
    ): array {
        if ($honorariosTotal <= 0) {
            throw new \InvalidArgumentException('Los honorarios deben ser mayores que cero.');
        }

        $cuotasEfectivas = $planPago === PlanPagoExpediente::Unico ? 1 : max(2, min(4, $numCuotas));
        $importes = $this->distribuirImportes($honorariosTotal, $cuotasEfectivas);
        $resultado = [];

        foreach ($importes as $indice => $importe) {
            $numero = $indice + 1;
            $fecha = 1 === $numero
                ? $fechaFirmaContrato->modify('+' . self::MARGEN_DIAS_PRIMERA_CUOTA . ' days')
                : $this->sumarMeses($fechaFirmaContrato, $numero - 1);

            $resultado[] = [
                'numero' => $numero,
                'importe' => $importe,
                'fechaVencimiento' => $fecha->format('Y-m-d'),
                'estado' => 'pendiente',
            ];
        }

        return $resultado;
    }

    /**
     * @param list<array{numero: int, importe: float, fechaVencimiento: string, estado: string}>|null $calendario
     */
    public function formatearPagosProgramados(?array $calendario, bool $provisional = false): string
    {
        if (null === $calendario || [] === $calendario) {
            return $provisional
                ? 'Calendario definitivo al firmar la hoja de encargo (1.ª cuota con 2 días de margen).'
                : 'Sin calendario de pagos definido.';
        }

        $lineas = [];
        foreach ($calendario as $cuota) {
            $fecha = \DateTimeImmutable::createFromFormat('Y-m-d', $cuota['fechaVencimiento']);
            $fechaLabel = $fecha instanceof \DateTimeImmutable
                ? $fecha->format('d/m/Y')
                : $cuota['fechaVencimiento'];
            $importe = number_format((float) $cuota['importe'], 2, ',', '.') . ' €';
            $lineas[] = sprintf('Cuota %d: %s — vencimiento %s', $cuota['numero'], $importe, $fechaLabel);
        }

        if ($provisional) {
            $lineas[] = '(Fechas referencia; el calendario definitivo se fija al firmar la hoja de encargo.)';
        }

        return implode("\n", $lineas);
    }

    public function formaPagoLabel(MetodoPagoExpediente $metodo, PlanPagoExpediente $plan, int $numCuotas): string
    {
        $metodoLabel = match ($metodo) {
            MetodoPagoExpediente::Manual => 'Transferencia bancaria',
            MetodoPagoExpediente::Digital => 'Pago digital (tarjeta)',
        };

        if ($plan === PlanPagoExpediente::Unico) {
            return $metodoLabel . ' — pago único';
        }

        return sprintf('%s — %d cuotas mensuales', $metodoLabel, max(2, min(4, $numCuotas)));
    }

    /**
     * Importe del cobro inicial en contratación (1.ª cuota o total si pago único).
     *
     * @param list<array{numero: int, importe: float, fechaVencimiento: string, estado: string}>|null $calendarioGuardado
     */
    public function importePagoInicial(
        float $honorariosTotal,
        PlanPagoExpediente $planPago,
        int $numCuotas,
        ?array $calendarioGuardado = null,
    ): float {
        if (null !== $calendarioGuardado && [] !== $calendarioGuardado) {
            return (float) $calendarioGuardado[0]['importe'];
        }

        $proyectado = $this->calcular(
            $honorariosTotal,
            $planPago,
            $numCuotas,
            new \DateTimeImmutable('today'),
        );

        return (float) $proyectado[0]['importe'];
    }

    public function formaPagoDetalle(
        MetodoPagoExpediente $metodo,
        PlanPagoExpediente $plan,
        int $numCuotas,
        float $honorarios,
    ): string {
        $base = $this->formaPagoLabel($metodo, $plan, $numCuotas);
        $total = number_format($honorarios, 2, ',', '.') . ' €';

        return $base . '. Importe total: ' . $total . '. '
            . 'La primera cuota vence en la fecha de firma del contrato (margen de '
            . self::MARGEN_DIAS_PRIMERA_CUOTA . ' días); las siguientes, el mismo día de los meses posteriores.';
    }

    /**
     * @return list<float>
     */
    private function distribuirImportes(float $total, int $numCuotas): array
    {
        $centimosTotal = (int) round($total * 100);
        $base = intdiv($centimosTotal, $numCuotas);
        $resto = $centimosTotal - ($base * $numCuotas);
        $importes = [];

        for ($i = 0; $i < $numCuotas; ++$i) {
            $centimos = $base + ($i < $resto ? 1 : 0);
            $importes[] = round($centimos / 100, 2);
        }

        return $importes;
    }

    private function sumarMeses(\DateTimeImmutable $fecha, int $meses): \DateTimeImmutable
    {
        return $fecha->modify('+' . $meses . ' months');
    }
}
