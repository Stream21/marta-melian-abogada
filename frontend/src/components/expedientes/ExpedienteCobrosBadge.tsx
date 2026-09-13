import type { ExpedienteResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatEuros } from '@/lib/pago-contratacion';

function formatFechaCorta(iso: string | null | undefined): string {
  if (!iso) return '—';
  try {
    // YYYY-MM-DD del calendario de cobros
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

/**
 * Badge de cobros con resumen al pasar el ratón (mismo patrón que documentación).
 */
export function ExpedienteCobrosBadge({ expediente }: { expediente: ExpedienteResponse }) {
  const resumen = expediente.resumenCobros;
  if (!resumen || resumen.total === 0) {
    return <span className="text-muted-foreground">—</span>;
  }

  const variant =
    resumen.vencidas > 0
      ? 'warning'
      : resumen.pagadas === resumen.total
        ? 'success'
        : resumen.pagadas > 0
          ? 'info'
          : 'secondary';

  return (
    <TooltipProvider delayDuration={200}>
      <Tooltip>
        <TooltipTrigger asChild>
          <button type="button" className="inline-flex max-w-full">
            <Badge variant={variant} className="max-w-full truncate cursor-help">
              {resumen.label}
              {resumen.vencidas > 0 ? ` · ${resumen.vencidas} venc.` : ''}
            </Badge>
          </button>
        </TooltipTrigger>
        <TooltipContent side="top" align="start" className="p-3">
          <div className="max-w-xs space-y-2 p-0.5">
            <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
              Cobros
            </p>
            <p className="text-xs text-muted-foreground">
              {formatEuros(resumen.cobrado)} de {formatEuros(resumen.importeTotal)}
              {resumen.pendiente > 0 ? ` · pendiente ${formatEuros(resumen.pendiente)}` : ''}
            </p>
            <ul className="space-y-1.5">
              {resumen.items.map((item) => (
                <li
                  key={item.nombre}
                  className="border-b border-border/60 pb-1.5 last:border-0 last:pb-0"
                >
                  <div className="flex items-start justify-between gap-3">
                    <span className="min-w-0 text-xs font-medium leading-snug text-foreground">
                      {item.nombre}
                      <span className="ml-1 font-normal text-muted-foreground">
                        ({formatEuros(item.importe)})
                      </span>
                    </span>
                    <span className="shrink-0 text-[10px] text-muted-foreground">
                      {formatFechaCorta(item.fecha)}
                    </span>
                  </div>
                  <p className="mt-0.5 text-[11px] text-muted-foreground">{item.estadoLabel}</p>
                </li>
              ))}
            </ul>
          </div>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
}
