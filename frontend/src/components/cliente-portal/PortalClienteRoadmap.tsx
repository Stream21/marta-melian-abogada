import { AlertTriangle, CheckCircle2, Clock } from 'lucide-react';
import type { AccesoExpedienteResponse, AccesoPasoResponse, FaseNegocio } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { indiceFaseNegocio, labelFaseNegocio, PORTAL_FASES } from '@/lib/portal-fases';
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
  const indiceActual = Math.max(0, indiceFaseNegocio(faseNegocio));
  const faseActual = PORTAL_FASES[indiceActual];
  const vencimiento = calcularVencimientoFase(fechaVencimientoFase);
  const textoVencimiento = textoVencimientoFase(fechaVencimientoFase);
  const mostrarSubpasos = faseNegocio === 'contratacion' && pasos.length > 0;

  const indiceSubpaso = mostrarSubpasos
    ? Math.max(0, pasos.findIndex((p) => p.paso === pasoActivo))
    : 0;

  return (
    <div className="rounded-xl border border-border bg-card p-4 shadow-sm">
      <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
        <span>
          Fase {faseActual?.orden ?? indiceActual + 1} de {PORTAL_FASES.length}
        </span>
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

      <p className="mt-2 text-sm font-semibold text-foreground">{labelFaseNegocio(faseNegocio)}</p>

      <div className="hidden sm:flex items-center justify-between gap-1 overflow-x-auto pt-3">
        {PORTAL_FASES.map((fase, i) => {
          const completada = i < indiceActual;
          const activa = i === indiceActual;
          const futura = i > indiceActual;

          return (
            <div key={fase.fase} className="flex min-w-0 flex-1 items-center">
              <div className="flex flex-1 flex-col items-center gap-1">
                <div
                  className={cn(
                    'flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-bold',
                    completada && 'border-emerald-500 bg-emerald-500 text-white',
                    activa && 'border-primary bg-primary/10 text-primary',
                    futura && 'border-border text-muted-foreground',
                  )}
                >
                  {completada ? <CheckCircle2 className="h-4 w-4" /> : fase.orden}
                </div>
                <span
                  className={cn(
                    'w-full truncate px-1 text-center text-[10px] font-medium',
                    activa ? 'text-foreground' : 'text-muted-foreground',
                  )}
                >
                  {fase.label}
                </span>
              </div>
              {i < PORTAL_FASES.length - 1 && (
                <div
                  className={cn('mx-1 h-0.5 w-4 shrink-0', completada ? 'bg-emerald-400' : 'bg-border')}
                />
              )}
            </div>
          );
        })}
      </div>

      {mostrarSubpasos && (
        <div className="mt-4 space-y-3 border-t border-border pt-4">
          <div className="flex items-center justify-between gap-2 text-[11px] text-muted-foreground">
            <span>
              Paso {indiceSubpaso + 1} de {pasos.length}
            </span>
            <span className="font-semibold text-foreground">
              {pasos[indiceSubpaso]?.label ?? pasos[0]?.label}
            </span>
          </div>

          <div className="flex items-center justify-center gap-1.5 sm:hidden">
            {pasos.map((paso) => {
              const activo = paso.paso === pasoActivo;
              const completado = paso.estado === 'validado_abogado';
              const enRevision = paso.estado === 'realizado_cliente';

              return (
                <div
                  key={paso.paso}
                  className={cn(
                    'h-2 flex-1 rounded-full transition-colors',
                    completado && 'bg-emerald-500',
                    enRevision && 'bg-amber-400',
                    activo && !completado && !enRevision && 'bg-primary',
                    !activo && !completado && !enRevision && 'bg-border',
                  )}
                  title={paso.label}
                />
              );
            })}
          </div>

          <div className="hidden items-center gap-2 overflow-x-auto sm:flex">
            {pasos.map((paso, i) => {
              const activo = paso.paso === pasoActivo;
              const completado = paso.estado === 'validado_abogado';
              const enRevision = paso.estado === 'realizado_cliente';

              return (
                <div key={paso.paso} className="flex items-center gap-2">
                  <div
                    className={cn(
                      'flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-[10px] font-bold',
                      completado && 'border-emerald-500 bg-emerald-500 text-white',
                      enRevision && 'border-amber-500 bg-amber-100 text-amber-800',
                      activo && !completado && !enRevision && 'border-primary bg-primary/10 text-primary',
                      !activo && !completado && !enRevision && 'border-border text-muted-foreground',
                    )}
                  >
                    {completado ? <CheckCircle2 className="h-3 w-3" /> : i + 1}
                  </div>
                  <span className="whitespace-nowrap text-[10px] text-muted-foreground">{paso.label}</span>
                  {i < pasos.length - 1 && (
                    <div className={cn('h-0.5 w-3 shrink-0', completado ? 'bg-emerald-400' : 'bg-border')} />
                  )}
                </div>
              );
            })}
          </div>
        </div>
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
