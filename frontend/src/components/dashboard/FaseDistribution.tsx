import type { DashboardKpisResponse } from '@/api/client';
import { cn } from '@/lib/utils';

const faseBarClass: Record<string, string> = {
  contratacion: 'bg-blue-600',
  documentacion: 'bg-violet-600',
  tramitacion: 'bg-amber-600',
  resolucion: 'bg-emerald-600',
};

interface FaseDistributionProps {
  porFase: DashboardKpisResponse['porFase'];
  totalActivos: number;
}

export function FaseDistribution({ porFase, totalActivos }: FaseDistributionProps) {
  return (
    <div className="panel p-5">
      <p className="section-label mb-4">Expedientes por fase</p>
      {totalActivos === 0 ? (
        <p className="text-sm text-muted-foreground">No hay expedientes activos.</p>
      ) : (
        <div className="space-y-3">
          {porFase.map((f) => {
            const pct = totalActivos > 0 ? Math.round((f.count / totalActivos) * 100) : 0;
            return (
              <div key={f.fase}>
                <div className="flex items-center justify-between text-sm mb-1.5">
                  <span className="font-medium text-foreground">{f.label}</span>
                  <span className="text-muted-foreground tabular-nums">
                    {f.count} · {pct}%
                  </span>
                </div>
                <div className="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                  <div
                    className={cn('h-full rounded-full transition-all', faseBarClass[f.fase] ?? 'bg-primary')}
                    style={{ width: `${pct}%` }}
                  />
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
