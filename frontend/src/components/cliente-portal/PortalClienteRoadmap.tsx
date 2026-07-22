import { AlertTriangle, CheckCircle2, Clock } from 'lucide-react';
import type { AccesoExpedienteResponse, AccesoPasoResponse, FaseNegocio } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { labelFaseNegocio } from '@/lib/portal-fases';
import { calcularVencimientoFase, textoVencimientoFase } from '@/lib/vencimiento-fase';
import { cn } from '@/lib/utils';

interface PortalClienteRoadmapProps {
  faseNegocio: FaseNegocio;
  fechaVencimientoFase?: string | null;
  pasos?: AccesoPasoResponse[];
  pasoActivo?: string | null;
}

export function PortalClienteRoadmap({
  faseNegocio,
  fechaVencimientoFase,
  pasos = [],
  pasoActivo,
}: PortalClienteRoadmapProps) {
  const vencimiento = calcularVencimientoFase(fechaVencimientoFase);
  const textoVencimiento = textoVencimientoFase(fechaVencimientoFase);
  const tienePasos = pasos.length > 0;
  const indiceActivo = tienePasos
    ? Math.max(0, pasos.findIndex((p) => p.paso === pasoActivo))
    : 0;
  const pasoActualLabel = tienePasos
    ? (pasos[indiceActivo]?.label ?? pasos[0]?.label)
    : labelFaseNegocio(faseNegocio);

  return (
    <div className="rounded-xl border border-border bg-card p-4 shadow-sm">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="min-w-0">
          <p className="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
            {labelFaseNegocio(faseNegocio)}
            {tienePasos ? ` · Paso ${indiceActivo + 1} de ${pasos.length}` : null}
          </p>
          <p className="mt-1 text-base font-semibold text-foreground">{pasoActualLabel}</p>
        </div>
        {textoVencimiento && (
          <Badge
            variant={vencimiento.vencido ? 'destructive' : vencimiento.urgente ? 'warning' : 'secondary'}
            className="gap-1"
          >
            {vencimiento.vencido || vencimiento.urgente ? (
              <AlertTriangle className="h-3 w-3" />
            ) : (
              <Clock className="h-3 w-3" />
            )}
            {textoVencimiento}
          </Badge>
        )}
      </div>

      {tienePasos && (
        <ol className="mt-4 flex items-stretch gap-2">
          {pasos.map((paso, i) => {
            const activo = paso.paso === pasoActivo;
            const completado = paso.estado === 'validado_abogado';
            const enRevision = paso.estado === 'realizado_cliente';

            return (
              <li key={paso.paso} className="min-w-0 flex-1">
                <div
                  className={cn(
                    'flex h-full flex-col items-center gap-1.5 rounded-lg border px-2 py-2.5 text-center transition-colors',
                    completado && 'border-emerald-200 bg-emerald-50',
                    enRevision && 'border-amber-200 bg-amber-50',
                    activo && !completado && !enRevision && 'border-primary bg-primary/5 shadow-sm',
                    !activo && !completado && !enRevision && 'border-border bg-muted/30',
                  )}
                >
                  <span
                    className={cn(
                      'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                      completado && 'bg-emerald-500 text-white',
                      enRevision && 'bg-amber-500 text-white',
                      activo && !completado && !enRevision && 'bg-primary text-primary-foreground',
                      !activo && !completado && !enRevision && 'bg-card text-muted-foreground border border-border',
                    )}
                  >
                    {completado ? <CheckCircle2 className="h-4 w-4" /> : i + 1}
                  </span>
                  <span
                    className={cn(
                      'line-clamp-2 text-[11px] font-medium leading-tight',
                      activo ? 'text-foreground' : 'text-muted-foreground',
                    )}
                  >
                    {paso.label}
                  </span>
                </div>
              </li>
            );
          })}
        </ol>
      )}
    </div>
  );
}

export function roadmapFromAcceso(data: AccesoExpedienteResponse): PortalClienteRoadmapProps {
  return {
    faseNegocio: data.faseNegocio,
    fechaVencimientoFase: data.fechaVencimientoFase,
    pasos: data.pasos,
    pasoActivo: data.pasoActivo,
  };
}
