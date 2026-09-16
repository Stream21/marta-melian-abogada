import { Info, NotebookPen } from 'lucide-react';
import type { ExpedienteResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

function formatFechaCorta(iso: string | null | undefined): string {
  if (!iso) return '—';
  try {
    return new Date(iso).toLocaleString('es-ES', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return '—';
  }
}

interface ExpedienteNotasCellProps {
  expediente: ExpedienteResponse;
  onOpen: () => void;
}

/**
 * Celda de notas del listado: clic abre el panel; hover solo si hay nota activa.
 */
export function ExpedienteNotasCell({ expediente, onOpen }: ExpedienteNotasCellProps) {
  const activas = expediente.notasActivas ?? 0;
  const ultima = expediente.ultimaNota;
  const tienePreview = activas > 0 && !!ultima && !ultima.archivada;

  const button = (
    <button
      type="button"
      className={cn(
        'inline-flex items-center gap-1.5 rounded-md px-1.5 py-1 text-muted-foreground',
        'transition-colors hover:bg-primary/10 hover:text-primary',
        activas > 0 && 'text-primary',
      )}
      title="Ver notas"
      onClick={(e) => {
        e.stopPropagation();
        onOpen();
      }}
      onDoubleClick={(e) => e.stopPropagation()}
    >
      <NotebookPen className="h-4 w-4 shrink-0" />
      {activas > 0 ? (
        <Badge variant="info" className="h-5 min-w-5 justify-center px-1.5 text-[10px]">
          {activas}
        </Badge>
      ) : (
        <span className="sr-only">Notas</span>
      )}
    </button>
  );

  if (!tienePreview) {
    return button;
  }

  return (
    <TooltipProvider delayDuration={200}>
      <Tooltip>
        <TooltipTrigger asChild>{button}</TooltipTrigger>
        <TooltipContent side="top" align="start" className="p-3">
          <div className="max-w-xs space-y-1.5 p-0.5">
            <p className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
              <Info className="h-3 w-3 shrink-0" aria-hidden />
              Última nota
            </p>
            <p className="whitespace-pre-wrap text-xs leading-snug text-foreground">
              {ultima.contenido.length > 280
                ? `${ultima.contenido.slice(0, 280)}…`
                : ultima.contenido}
            </p>
            <p className="text-[11px] text-muted-foreground">
              {formatFechaCorta(ultima.createdAt)}
            </p>
          </div>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
}
