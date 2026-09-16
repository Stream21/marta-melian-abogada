import {
  Banknote,
  Wallet,
  TrendingUp,
  TrendingDown,
  Folders,
  CalendarClock,
  FileWarning,
  AlertTriangle,
} from 'lucide-react';
import type { DashboardKpisResponse } from '@/api/client';
import { cn } from '@/lib/utils';

const fmtEur = (n: number) =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n);

interface StatsCardsProps {
  financiero: DashboardKpisResponse['financiero'];
  operativo: DashboardKpisResponse['operativo'];
  periodoLabel: string;
}

export function StatsCards({ financiero, operativo, periodoLabel }: StatsCardsProps) {
  const beneficioPositivo = financiero.beneficioMes >= 0;
  const plazosAtencion = operativo.plazosUrgentes + operativo.plazosVencidos;

  return (
    <div className="space-y-6">
      <div>
        <p className="section-label mb-3">Financiero · {periodoLabel}</p>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <KpiCard
            label="Total cobros"
            value={fmtEur(financiero.cobrosMes)}
            hint="Pagos confirmados este mes"
            icon={Banknote}
            accent="text-emerald-600"
          />
          <KpiCard
            label="Total gastos"
            value={fmtEur(financiero.gastosMes)}
            hint="Gastos del despacho este mes"
            icon={Wallet}
            accent="text-amber-700"
          />
          <KpiCard
            label="Beneficio"
            value={fmtEur(financiero.beneficioMes)}
            hint="Cobros − gastos del mes"
            icon={beneficioPositivo ? TrendingUp : TrendingDown}
            accent={beneficioPositivo ? 'text-emerald-600' : 'text-destructive'}
            valueClassName={beneficioPositivo ? 'text-emerald-700' : 'text-destructive'}
          />
        </div>
      </div>

      <div>
        <p className="section-label mb-3">Operativo</p>
        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
          <KpiCard
            label="Expedientes activos"
            value={String(operativo.expedientesActivos)}
            hint="Abiertos en el despacho"
            icon={Folders}
            accent="text-primary"
            compact
          />
          <KpiCard
            label="A revisar (≤7 días)"
            value={String(plazosAtencion)}
            hint={
              operativo.plazosVencidos > 0
                ? `${operativo.plazosVencidos} vencido${operativo.plazosVencidos === 1 ? '' : 's'} · ${operativo.plazosUrgentes} próximos`
                : 'Plazos de fase y cuotas'
            }
            icon={CalendarClock}
            accent={operativo.plazosVencidos > 0 ? 'text-destructive' : 'text-amber-700'}
            compact
          />
          <KpiCard
            label="Docs. pendientes"
            value={String(operativo.documentacionPendienteRevision)}
            hint="Pendientes de revisión"
            icon={FileWarning}
            accent="text-violet-700"
            compact
          />
          <KpiCard
            label="Cobros vencidos"
            value={fmtEur(financiero.cobrosVencidosImporte)}
            hint={`${financiero.cobrosVencidosCount} cuota${financiero.cobrosVencidosCount === 1 ? '' : 's'} · pendiente ${fmtEur(financiero.cobrosPendientesImporte)}`}
            icon={AlertTriangle}
            accent={financiero.cobrosVencidosCount > 0 ? 'text-destructive' : 'text-muted-foreground'}
            compact
          />
        </div>
      </div>
    </div>
  );
}

interface KpiCardProps {
  label: string;
  value: string;
  hint: string;
  icon: React.ElementType;
  accent: string;
  valueClassName?: string;
  compact?: boolean;
}

function KpiCard({ label, value, hint, icon: Icon, accent, valueClassName, compact }: KpiCardProps) {
  return (
    <div
      className={cn(
        'panel p-5 hover:shadow-md transition-shadow',
        compact ? 'min-h-[7.5rem]' : 'min-h-[8.5rem]',
      )}
    >
      <div className="flex flex-col h-full justify-between gap-3">
        <div className="flex items-start justify-between gap-2">
          <p className="section-label">{label}</p>
          <div className={cn('rounded-lg bg-primary/10 p-2', accent)}>
            <Icon className="h-4 w-4" />
          </div>
        </div>
        <div>
          <p className={cn('text-3xl font-bold tracking-tight text-foreground', valueClassName)}>
            {value}
          </p>
          <p className="mt-1 text-[11px] font-medium text-muted-foreground">{hint}</p>
        </div>
      </div>
    </div>
  );
}
