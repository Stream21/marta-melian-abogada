<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Expediente;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentStatus;

final class CalendarioCobrosService
{
    public function __construct(
        private CalendarioPagoService $calendarioPagoService,
    ) {
    }

    /**
     * @param list<Payment> $payments
     *
     * @return list<array<string, mixed>>
     */
    public function listarCobros(Expediente $expediente, array $payments): array
    {
        $calendario = $this->resolverCalendario($expediente);
        if ([] === $calendario) {
            return [];
        }

        $paymentsPorCuota = [];
        $pagosSinCuotaPagados = [];
        foreach ($payments as $payment) {
            $numero = $payment->cuotaNumero();
            if (null !== $numero) {
                $paymentsPorCuota[$numero] = $payment;
            } elseif (PaymentStatus::Paid === $payment->status()) {
                $pagosSinCuotaPagados[] = $payment;
            }
        }

        $cobros = [];
        $hoy = new \DateTimeImmutable('today');

        foreach ($calendario as $cuota) {
            $numero = (int) ($cuota['numero'] ?? 0);
            $importe = (float) ($cuota['importe'] ?? 0);
            $fechaRaw = (string) ($cuota['fechaVencimiento'] ?? '');
            $fecha = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaRaw) ?: null;

            $payment = $paymentsPorCuota[$numero] ?? null;
            $estado = (string) ($cuota['estado'] ?? 'pendiente');

            if (null === $payment && [] !== $pagosSinCuotaPagados) {
                foreach ($pagosSinCuotaPagados as $idx => $orphan) {
                    if (abs((float) $orphan->amount() - $importe) < 0.01) {
                        $payment = $orphan;
                        unset($pagosSinCuotaPagados[$idx]);
                        break;
                    }
                }
            }

            if ('pagado' !== $estado && null !== $payment && PaymentStatus::Paid === $payment->status()) {
                $estado = 'pagado';
            } elseif ('pagado' !== $estado && null !== $fecha && $fecha < $hoy) {
                $estado = 'vencido';
            } elseif (null !== $payment && PaymentStatus::Pending === $payment->status()) {
                $estado = 'enlace_pendiente';
            }

            $cobros[] = [
                'numero' => $numero,
                'importe' => $importe,
                'fechaVencimiento' => $fechaRaw,
                'estado' => $estado,
                'estadoLabel' => $this->estadoLabel($estado),
                'paymentId' => $payment?->id()->value(),
                'paymentType' => $payment?->type()->value,
                'paymentStatus' => $payment?->status()->value,
                'holdedEstado' => $payment?->holdedEstado()->value,
                'holdedEstadoLabel' => $payment?->holdedEstado()->label(),
                'holdedSyncError' => $payment?->holdedSyncError(),
                'pdfUrl' => $payment?->invoicePdfUrl(),
            ];
        }

        return $cobros;
    }

    /**
     * @return array{total: float, cobrado: float, pendiente: float, vencido: float}
     */
    public function resumen(array $cobros): array
    {
        $total = 0.0;
        $cobrado = 0.0;
        $pendiente = 0.0;
        $vencido = 0.0;

        foreach ($cobros as $cobro) {
            $importe = (float) $cobro['importe'];
            $total += $importe;

            if ('pagado' === $cobro['estado']) {
                $cobrado += $importe;
            } elseif ('vencido' === $cobro['estado']) {
                $vencido += $importe;
                $pendiente += $importe;
            } else {
                $pendiente += $importe;
            }
        }

        return [
            'total' => round($total, 2),
            'cobrado' => round($cobrado, 2),
            'pendiente' => round($pendiente, 2),
            'vencido' => round($vencido, 2),
        ];
    }

    /**
     * @param list<array{numero: int, importe: float, fechaVencimiento: string, estado: string}>|null $calendario
     *
     * @return list<array{numero: int, importe: float, fechaVencimiento: string, estado: string}>
     */
    public function marcarCuotaPagada(?array $calendario, int $cuotaNumero): ?array
    {
        if (null === $calendario || [] === $calendario) {
            return $calendario;
        }

        $actualizado = [];
        foreach ($calendario as $cuota) {
            if ((int) ($cuota['numero'] ?? 0) === $cuotaNumero) {
                $cuota['estado'] = 'pagado';
            }
            $actualizado[] = $cuota;
        }

        return $actualizado;
    }

    /**
     * @return list<array{numero: int, importe: float, fechaVencimiento: string, estado: string}>
     */
    private function resolverCalendario(Expediente $expediente): array
    {
        $guardado = $expediente->calendarioPagos();
        if (null !== $guardado && [] !== $guardado) {
            return $guardado;
        }

        if ($expediente->honorariosAcordados() <= 0) {
            return [];
        }

        $fechaBase = $expediente->fechaFirmaContrato() ?? new \DateTimeImmutable('today');

        return $this->calendarioPagoService->calcular(
            $expediente->honorariosAcordados(),
            $expediente->planPago(),
            $expediente->numCuotas(),
            $fechaBase,
        );
    }

    private function estadoLabel(string $estado): string
    {
        return match ($estado) {
            'pagado' => 'Cobrado',
            'vencido' => 'Vencido',
            'enlace_pendiente' => 'Enlace enviado',
            default => 'Pendiente',
        };
    }
}
