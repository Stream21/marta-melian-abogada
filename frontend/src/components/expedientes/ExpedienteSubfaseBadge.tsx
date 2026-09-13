import type { ExpedienteResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';

function formatFechaCorta(iso: string | null | undefined): string {
  if (!iso) return '—';
  try {
    const date = iso.includes('T') ? new Date(iso) : new Date(`${iso}T12:00:00`);
    return date.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    });
  } catch {
    return '—';
  }
}

function SubfaseTooltipList({
  title,
  rows,
}: {
  title: string;
  rows: Array<{ name: string; status: string; date: string | null; hint?: string }>;
}) {
  return (
    <div className="max-w-xs space-y-2 p-0.5">
      <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{title}</p>
      <ul className="space-y-1.5">
        {rows.length === 0 ? (
          <li className="text-xs text-muted-foreground">Sin detalle</li>
        ) : (
          rows.map((row, index) => (
            <li
              key={`${row.name}-${row.date ?? 'sin-fecha'}-${index}`}
              className="border-b border-border/60 pb-1.5 last:border-0 last:pb-0"
            >              <div className="flex items-start justify-between gap-3">
                <span className="min-w-0 text-xs font-medium leading-snug text-foreground">
                  {row.name}
                  {row.hint ? (
                    <span className="ml-1 font-normal text-muted-foreground">({row.hint})</span>
                  ) : null}
                </span>
                <span className="shrink-0 text-[10px] text-muted-foreground">
                  {formatFechaCorta(row.date)}
                </span>
              </div>
              <p className="mt-0.5 text-[11px] text-muted-foreground">{row.status}</p>
            </li>
          ))
        )}
      </ul>
    </div>
  );
}

/**
 * Badge de subfase con resumen al pasar el ratón (mejor que modal en un listado denso).
 */
export function ExpedienteSubfaseBadge({ expediente }: { expediente: ExpedienteResponse }) {
  if (expediente.faseNegocio === 'contratacion' && expediente.subfaseContratacion) {
    const sub = expediente.subfaseContratacion;
    const label = `${sub.orden}/${sub.total} ${sub.label}${
      sub.estado === 'esperando_abogado' ? ' · revisión' : ''
    }`;
    const rows = (sub.items ?? []).map((item) => ({
      name: item.label,
      status: item.estadoLabel,
      date: item.fecha,
    }));

    return (
      <TooltipProvider delayDuration={200}>
        <Tooltip>
          <TooltipTrigger asChild>
            <button type="button" className="inline-flex max-w-full">
              <Badge variant="secondary" className="max-w-full truncate cursor-help">
                {label}
              </Badge>
            </button>
          </TooltipTrigger>
          <TooltipContent side="top" align="start" className="p-3">
            <SubfaseTooltipList title="Pasos de contratación" rows={rows} />
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    );
  }

  if (expediente.faseNegocio === 'documentacion' && expediente.subfaseDocumentacion) {
    const sub = expediente.subfaseDocumentacion;
    const rows = sub.items.map((item) => ({
      name: item.nombre,
      status: item.estadoLabel,
      date: item.fecha,
      hint: item.obligatorio ? 'obl.' : undefined,
    }));

    return (
      <TooltipProvider delayDuration={200}>
        <Tooltip>
          <TooltipTrigger asChild>
            <button type="button" className="inline-flex max-w-full">
              <Badge
                variant={sub.enRevision > 0 ? 'warning' : 'secondary'}
                className="max-w-full truncate cursor-help"
              >
                {sub.label}
              </Badge>
            </button>
          </TooltipTrigger>
          <TooltipContent side="top" align="start" className="p-3">
            <SubfaseTooltipList title="Documentación" rows={rows} />
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    );
  }

  if (expediente.faseNegocio === 'tramitacion' && expediente.subfaseTramitacionLabel) {
    const recopilacion = expediente.subfaseTramitacion === 'pendiente_requerimiento';
    const detalle = expediente.subfaseTramitacionDetalle;
    const rows = (detalle?.items ?? []).map((item) => ({
      name: item.nombre,
      status: item.estadoLabel,
      date: item.fecha,
    }));
    const badge = (
      <Badge
        variant={recopilacion ? 'warning' : 'secondary'}
        className={`max-w-full truncate${rows.length > 0 ? ' cursor-help' : ''}`}
      >
        {expediente.subfaseTramitacionLabel}
      </Badge>
    );

    if (rows.length === 0) {
      return badge;
    }

    return (
      <TooltipProvider delayDuration={200}>
        <Tooltip>
          <TooltipTrigger asChild>
            <button type="button" className="inline-flex max-w-full">
              {badge}
            </button>
          </TooltipTrigger>
          <TooltipContent side="top" align="start" className="p-3">
            <SubfaseTooltipList title="Tramitación · presentaciones" rows={rows} />
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    );
  }

  return <span className="text-muted-foreground">—</span>;
}
