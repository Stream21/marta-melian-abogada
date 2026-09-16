<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\CobrosResumenListadoService;
use App\Application\Service\ExpedienteAvisosAggregator;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PaymentHoldedEstado;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\GastoRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;

final class ObtenerDashboardKpisUseCase
{
    private const VENCIMIENTO_DIAS_REVISION = 7;

    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private GastoRepositoryInterface $gastoRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private CobrosResumenListadoService $cobrosResumenListado,
        private ExpedienteAvisosAggregator $avisosAggregator,
        private ObtenerNotificacionesRecientesUseCase $obtenerNotificaciones,
    ) {
    }

    /**
     * @return array{
     *   periodo: array{desde: string, hasta: string, label: string},
     *   financiero: array{
     *     cobrosMes: float,
     *     gastosMes: float,
     *     beneficioMes: float,
     *     cobrosPendientesImporte: float,
     *     cobrosVencidosImporte: float,
     *     cobrosVencidosCount: int
     *   },
     *   operativo: array{
     *     expedientesActivos: int,
     *     plazosUrgentes: int,
     *     plazosVencidos: int,
     *     documentacionPendienteRevision: int,
     *     contratacionPendienteRevision: int,
     *     stripePendientes: int,
     *     holdedSyncPendientes: int,
     *     notificacionesSinLeer: int
     *   },
     *   porFase: list<array{fase: string, label: string, count: int}>,
     *   vencimientosProximos: list<array<string, mixed>>,
     *   actividadReciente: list<array<string, mixed>>
     * }
     */
    public function __invoke(): array
    {
        $hoy = new \DateTimeImmutable('today');
        $mesInicio = $hoy->modify('first day of this month')->setTime(0, 0, 0);
        $mesFin = $hoy->modify('last day of this month')->setTime(23, 59, 59);

        $financieroPagos = $this->aggregarPagos($mesInicio, $mesFin);
        $gastosMes = $this->sumarGastosMes($mesInicio, $mesFin);

        $expedientes = array_values(array_filter(
            $this->expedienteRepository->findAll(),
            static fn (Expediente $e): bool => EstadoExpediente::Abierto === $e->estado(),
        ));

        $resumenCobros = $this->cobrosResumenListado->aggregate($expedientes);
        $avisos = $this->avisosAggregator->aggregate($expedientes);

        $cobrosPendientesImporte = 0.0;
        $cobrosVencidosImporte = 0.0;
        $cobrosVencidosCount = 0;
        $documentacionPendiente = 0;
        $contratacionPendiente = 0;
        $porFaseCounts = [];
        foreach (FaseNegocioExpediente::cases() as $fase) {
            $porFaseCounts[$fase->value] = 0;
        }

        $vencimientos = [];

        foreach ($expedientes as $expediente) {
            $id = $expediente->id()->value();
            $fase = $expediente->faseNegocio();
            ++$porFaseCounts[$fase->value];

            $aviso = $avisos[$id] ?? null;
            if (null !== $aviso) {
                $documentacionPendiente += (int) $aviso['documentacion'];
                $contratacionPendiente += (int) $aviso['contratacion'];
            }

            $resumen = $resumenCobros[$id] ?? null;
            if (null !== $resumen) {
                $cobrosPendientesImporte += (float) $resumen['pendiente'];
                $cobrosVencidosCount += (int) $resumen['vencidas'];
                foreach ($resumen['items'] as $item) {
                    if ('vencido' === ($item['estado'] ?? '')) {
                        $cobrosVencidosImporte += (float) ($item['importe'] ?? 0);
                    }
                }
            }

            foreach ($this->plazosActivos($expediente, $resumen) as $plazo) {
                $dias = $this->diasCalendarioHasta($hoy, $plazo['fecha']);
                if (null === $dias) {
                    continue;
                }
                // Ventana de revisión: ya vencidos o ≤ 7 días.
                if ($dias > self::VENCIMIENTO_DIAS_REVISION) {
                    continue;
                }

                $vencimientos[] = [
                    'expedienteId' => $id,
                    'expedienteNumero' => $expediente->numero(),
                    'clienteNombre' => $expediente->clientName(),
                    'tramiteNombre' => $expediente->caseReference(),
                    'tipo' => $plazo['tipo'],
                    'label' => $plazo['label'],
                    'fecha' => $plazo['fecha'],
                    'diasRestantes' => $dias,
                    'urgencia' => $this->urgenciaLabel($dias),
                    'faseNegocio' => $fase->value,
                    'faseNegocioLabel' => $fase->label(),
                ];
            }
        }

        usort(
            $vencimientos,
            static fn (array $a, array $b): int => $a['diasRestantes'] <=> $b['diasRestantes']
                ?: strcmp((string) $a['expedienteNumero'], (string) $b['expedienteNumero']),
        );

        $plazosVencidos = 0;
        $plazosUrgentes = 0;
        foreach ($vencimientos as $v) {
            if ($v['diasRestantes'] < 0) {
                ++$plazosVencidos;
            } else {
                ++$plazosUrgentes;
            }
        }

        $notificaciones = ($this->obtenerNotificaciones)(8);

        $porFase = [];
        foreach (FaseNegocioExpediente::cases() as $fase) {
            $porFase[] = [
                'fase' => $fase->value,
                'label' => $fase->label(),
                'count' => $porFaseCounts[$fase->value] ?? 0,
            ];
        }

        $cobrosMes = $financieroPagos['cobrosMes'];
        $beneficioMes = round($cobrosMes - $gastosMes, 2);

        return [
            'periodo' => [
                'desde' => $mesInicio->format('Y-m-d'),
                'hasta' => $hoy->format('Y-m-d'),
                'label' => 'Mes actual',
            ],
            'financiero' => [
                'cobrosMes' => round($cobrosMes, 2),
                'gastosMes' => round($gastosMes, 2),
                'beneficioMes' => $beneficioMes,
                'cobrosPendientesImporte' => round($cobrosPendientesImporte, 2),
                'cobrosVencidosImporte' => round($cobrosVencidosImporte, 2),
                'cobrosVencidosCount' => $cobrosVencidosCount,
            ],
            'operativo' => [
                'expedientesActivos' => count($expedientes),
                'plazosUrgentes' => $plazosUrgentes,
                'plazosVencidos' => $plazosVencidos,
                'documentacionPendienteRevision' => $documentacionPendiente,
                'contratacionPendienteRevision' => $contratacionPendiente,
                'stripePendientes' => $financieroPagos['stripePendientes'],
                'holdedSyncPendientes' => $financieroPagos['holdedSyncPendientes'],
                'notificacionesSinLeer' => (int) $notificaciones['total'],
            ],
            'porFase' => $porFase,
            'vencimientosProximos' => $vencimientos,
            'actividadReciente' => $notificaciones['items'],
        ];
    }

    /**
     * @return array{cobrosMes: float, stripePendientes: int, holdedSyncPendientes: int}
     */
    private function aggregarPagos(\DateTimeImmutable $mesInicio, \DateTimeImmutable $mesFin): array
    {
        $cobrosMes = 0.0;
        $stripePendientes = 0;
        $holdedSyncPendientes = 0;

        foreach ($this->paymentRepository->findAll() as $payment) {
            if (PaymentStatus::Paid === $payment->status()
                && $payment->createdAt() >= $mesInicio
                && $payment->createdAt() <= $mesFin
            ) {
                $cobrosMes += (float) $payment->amount();
            }

            if (in_array($payment->holdedEstado(), [PaymentHoldedEstado::PendienteSync, PaymentHoldedEstado::Error], true)) {
                ++$holdedSyncPendientes;
            }

            if (PaymentStatus::Pending === $payment->status() && PaymentType::Link === $payment->type()) {
                ++$stripePendientes;
            }
        }

        return [
            'cobrosMes' => $cobrosMes,
            'stripePendientes' => $stripePendientes,
            'holdedSyncPendientes' => $holdedSyncPendientes,
        ];
    }

    private function sumarGastosMes(\DateTimeImmutable $mesInicio, \DateTimeImmutable $mesFin): float
    {
        $total = 0.0;
        foreach ($this->gastoRepository->findAll() as $gasto) {
            $fecha = $gasto->fecha()->setTime(0, 0, 0);
            if ($fecha >= $mesInicio && $fecha <= $mesFin) {
                $total += (float) $gasto->importe();
            }
        }

        return $total;
    }

    /**
     * @param array{
     *     items?: list<array{nombre: string, estado: string, fecha: string|null}>
     * }|null $resumen
     *
     * @return list<array{tipo: string, label: string, fecha: string}>
     */
    private function plazosActivos(Expediente $expediente, ?array $resumen): array
    {
        $items = [];

        $fase = $expediente->fechaVencimientoFase();
        if (null !== $fase) {
            $items[] = [
                'tipo' => 'fase',
                'label' => 'Plazo de fase',
                'fecha' => $fase->format('Y-m-d'),
            ];
        }

        foreach ($resumen['items'] ?? [] as $cobro) {
            if ('pagado' === ($cobro['estado'] ?? '')) {
                continue;
            }
            $fecha = $cobro['fecha'] ?? null;
            if (null === $fecha || '' === trim($fecha)) {
                continue;
            }
            $items[] = [
                'tipo' => 'cuota',
                'label' => (string) ($cobro['nombre'] ?? 'Cuota'),
                'fecha' => substr($fecha, 0, 10),
            ];
        }

        return $items;
    }

    private function diasCalendarioHasta(\DateTimeImmutable $hoy, string $fecha): ?int
    {
        $venc = \DateTimeImmutable::createFromFormat('!Y-m-d', substr($fecha, 0, 10));
        if (false === $venc) {
            return null;
        }

        $diff = $hoy->diff($venc);

        return (int) $diff->format('%r%a');
    }

    private function urgenciaLabel(int $diasRestantes): string
    {
        if ($diasRestantes < 0) {
            return 'vencido';
        }
        if (0 === $diasRestantes) {
            return 'hoy';
        }
        if (1 === $diasRestantes) {
            return 'manana';
        }
        if ($diasRestantes <= 3) {
            return 'proximos';
        }

        return 'semana';
    }
}
