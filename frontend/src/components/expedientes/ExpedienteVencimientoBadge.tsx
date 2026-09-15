import type { ExpedienteResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { calcularVencimientoFase, textoVencimientoFase } from '@/lib/vencimiento-fase';
import {
  listarVencimientosActivos,
  proximoVencimiento,
  type VencimientoActivo,
} from '@/lib/vencimiento-proximo';

function formatFechaCorta(iso: string): string {
  try {
    const date = new Date(`${iso.slice(0, 10)}T12:00:00`);
    return date.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    });
  } catch {
    return iso;
  }
}

function tipoLabel(tipo: VencimientoActivo['tipo']): string {
  return tipo === 'fase' ? 'Plazo de fase' : 'Cuota';
}

function VencimientoBadgeContent({
  vencimiento,
  interactive = false,
}: {
  vencimiento: VencimientoActivo;
  interactive?: boolean;
}) {
  const info = calcularVencimientoFase(vencimiento.fecha);
  const texto = textoVencimientoFase(vencimiento.fecha);
  if (!texto || !info.fechaFormateada) {
    return <span className="text-muted-foreground">—</span>;
  }

  const className = interactive ? 'max-w-full truncate cursor-help' : 'max-w-full truncate';

  if (info.vencido) {
    return (
      <Badge variant="destructive" className={className}>
        {texto}
      </Badge>
    );
  }
  if (info.urgente) {
    return (
      <Badge variant="warning" className={className}>
        {texto}
      </Badge>
    );
  }
  return (
    <span className={interactive ? 'text-muted-foreground cursor-help' : 'text-muted-foreground'}>
      {info.fechaFormateada}
    </span>
  );
}

/**
 * Muestra el vencimiento más próximo (fase o cuota).
 * Al pasar el ratón: qué es ese plazo y el resto de vencimientos activos.
 */
export function ExpedienteVencimientoBadge({ expediente }: { expediente: ExpedienteResponse }) {
  const activos = listarVencimientosActivos(expediente);
  const proximo = proximoVencimiento(expediente);

  if (!proximo) {
    return <span className="text-muted-foreground">—</span>;
  }

  return (
    <TooltipProvider delayDuration={200}>
      <Tooltip>
        <TooltipTrigger asChild>
          <button type="button" className="inline-flex max-w-full">
            <VencimientoBadgeContent vencimiento={proximo} interactive />
          </button>
        </TooltipTrigger>
        <TooltipContent side="top" align="start" className="p-3">
          <div className="max-w-xs space-y-2 p-0.5">
            <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
              Próximo vencimiento
            </p>
            <div className="border-b border-border/60 pb-2">
              <div className="flex items-start justify-between gap-3">
                <span className="min-w-0 text-xs font-medium leading-snug text-foreground">
                  {proximo.label}
                </span>
                <span className="shrink-0 text-[10px] text-muted-foreground">
                  {formatFechaCorta(proximo.fecha)}
                </span>
              </div>
              <p className="mt-0.5 text-[11px] text-muted-foreground">
                {tipoLabel(proximo.tipo)} · {textoVencimientoFase(proximo.fecha)}
              </p>
            </div>
            {activos.length > 1 && (
              <>
                <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                  Otros plazos
                </p>
                <ul className="space-y-1.5">
                  {activos
                    .filter(
                      (item) =>
                        !(
                          item.tipo === proximo.tipo &&
                          item.fecha === proximo.fecha &&
                          item.label === proximo.label
                        ),
                    )
                    .map((item) => {
                      const info = calcularVencimientoFase(item.fecha);
                      return (
                        <li
                          key={`${item.tipo}-${item.label}-${item.fecha}`}
                          className="border-b border-border/60 pb-1.5 last:border-0 last:pb-0"
                        >
                          <div className="flex items-start justify-between gap-3">
                            <span className="min-w-0 text-xs font-medium leading-snug text-foreground">
                              {item.label}
                            </span>
                            <span className="shrink-0 text-[10px] text-muted-foreground">
                              {formatFechaCorta(item.fecha)}
                            </span>
                          </div>
                          <p className="mt-0.5 text-[11px] text-muted-foreground">
                            {tipoLabel(item.tipo)}
                            {info.vencido
                              ? ' · vencido'
                              : info.diasRestantes === 0
                                ? ' · hoy'
                                : ''}
                          </p>
                        </li>
                      );
                    })}
                </ul>
              </>
            )}
          </div>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
}
