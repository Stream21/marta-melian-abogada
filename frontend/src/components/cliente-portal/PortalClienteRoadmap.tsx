import type { AccesoExpedienteResponse, AccesoPasoResponse, FaseNegocio } from '@/api/client';
import { labelFaseNegocio } from '@/lib/portal-fases';
import { cn } from '@/lib/utils';

interface PortalClienteRoadmapProps {
  faseNegocio: FaseNegocio;
  fechaVencimientoFase?: string | null;
  pasos?: AccesoPasoResponse[];
  pasoActivo?: string | null;
  /** Subtle progress line instead of large step cards. */
  compact?: boolean;
  /** Solo barras, sin etiqueta de paso (header focus). */
  barsOnly?: boolean;
}

/**
 * Paso a destacar en el roadmap:
 * 1) pasoActivo del cliente
 * 2) subfase enviada esperando abogado (realizado_cliente)
 * 3) primera pendiente
 */
export function resolverPasoRoadmap(
  pasos: AccesoPasoResponse[],
  pasoActivo?: string | null,
): AccesoPasoResponse | null {
  if (pasos.length === 0) return null;

  if (pasoActivo) {
    const activo = pasos.find((p) => p.paso === pasoActivo);
    if (activo) return activo;
  }

  const enRevision = pasos.find((p) => p.estado === 'realizado_cliente');
  if (enRevision) return enRevision;

  const pendiente = pasos.find(
    (p) => p.estado !== 'validado_abogado' && p.estado !== 'realizado_cliente',
  );
  if (pendiente) return pendiente;

  return pasos[pasos.length - 1] ?? null;
}

function etiquetaPasoRoadmap(paso: AccesoPasoResponse): string {
  if (paso.estado === 'realizado_cliente') {
    return `${paso.label} · en revisión`;
  }
  return paso.label;
}

export function PortalClienteRoadmap({
  faseNegocio,
  pasos = [],
  pasoActivo,
  compact = false,
  barsOnly = false,
}: PortalClienteRoadmapProps) {
  const tienePasos = pasos.length > 0;
  const pasoDestacado = resolverPasoRoadmap(pasos, pasoActivo);
  const indiceActivo = pasoDestacado
    ? Math.max(0, pasos.findIndex((p) => p.paso === pasoDestacado.paso))
    : 0;
  const codigoDestacado = pasoDestacado?.paso ?? null;
  const pasoActualLabel = pasoDestacado
    ? etiquetaPasoRoadmap(pasoDestacado)
    : labelFaseNegocio(faseNegocio);

  if (!tienePasos) {
    return (
      <p className="text-[11px] font-medium text-muted-foreground">
        {labelFaseNegocio(faseNegocio)}
      </p>
    );
  }

  if (compact) {
    const barras = (
      <div className="flex min-w-0 flex-1 gap-1" role="list">
        {pasos.map((paso) => {
          const destacado = paso.paso === codigoDestacado;
          const completado = paso.estado === 'validado_abogado';
          const enRevision = paso.estado === 'realizado_cliente';
          return (
            <div
              key={paso.paso}
              role="listitem"
              title={etiquetaPasoRoadmap(paso)}
              className={cn(
                'h-0.5 flex-1 rounded-full transition-colors duration-200',
                completado && 'bg-emerald-500',
                enRevision && 'bg-amber-400',
                destacado && !completado && !enRevision && 'bg-primary',
                !destacado && !completado && !enRevision && 'bg-border',
              )}
            />
          );
        })}
      </div>
    );

    if (barsOnly) {
      return (
        <div className="w-full" aria-label={`Progreso: ${pasoActualLabel}`}>
          {barras}
        </div>
      );
    }

    return (
      <div className="flex items-center gap-3" aria-label={`Progreso: ${pasoActualLabel}`}>
        <p className="shrink-0 text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
          {indiceActivo + 1}/{pasos.length} · {pasoActualLabel}
        </p>
        {barras}
      </div>
    );
  }

  return (
    <div className="space-y-1.5">
      <p className="text-[11px] text-muted-foreground">
        <span className="font-medium text-foreground/80">
          {labelFaseNegocio(faseNegocio)} · Paso {indiceActivo + 1} de {pasos.length}
        </span>
        <span className="mx-1.5 text-border">·</span>
        <span>{pasoActualLabel}</span>
      </p>
      <div className="flex gap-1">
        {pasos.map((paso) => {
          const destacado = paso.paso === codigoDestacado;
          const completado = paso.estado === 'validado_abogado';
          const enRevision = paso.estado === 'realizado_cliente';
          return (
            <div
              key={paso.paso}
              className={cn(
                'h-1 flex-1 rounded-full',
                completado && 'bg-emerald-500',
                enRevision && 'bg-amber-400',
                destacado && !completado && !enRevision && 'bg-primary',
                !destacado && !completado && !enRevision && 'bg-border',
              )}
            />
          );
        })}
      </div>
    </div>
  );
}

export function roadmapFromAcceso(data: AccesoExpedienteResponse): PortalClienteRoadmapProps {
  if (data.faseNegocio === 'tramitacion' && data.tramitacion?.timeline?.length) {
    const pasos: AccesoPasoResponse[] = data.tramitacion.timeline.map((step) => ({
      paso: step.id,
      label: step.label,
      estado:
        step.estado === 'completado'
          ? 'validado_abogado'
          : step.estado === 'activo'
            ? 'pendiente'
            : 'pendiente',
      estadoLabel: step.estado,
      esActivo: step.estado === 'activo',
    }));
    const pasoActivo =
      data.tramitacion.timeline.find((s) => s.estado === 'activo')?.id ??
      pasos[pasos.length - 1]?.paso ??
      null;

    return {
      faseNegocio: data.faseNegocio,
      fechaVencimientoFase: data.fechaVencimientoFase,
      pasos,
      pasoActivo,
    };
  }

  return {
    faseNegocio: data.faseNegocio,
    fechaVencimientoFase: data.fechaVencimientoFase,
    pasos: data.pasos,
    pasoActivo: data.pasoActivo,
  };
}
