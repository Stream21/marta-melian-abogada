import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Archive, ArchiveRestore, NotebookPen } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { api, type ExpedienteNotaResponse } from '@/api/client';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

function formatFechaNota(iso: string): string {
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

interface ExpedienteNotasPanelProps {
  expedienteId: string;
  /** Si false, no carga hasta que el contenedor esté visible (p. ej. sheet cerrado). */
  enabled?: boolean;
  /** Variante compacta para panel lateral. */
  compact?: boolean;
  className?: string;
}

/**
 * Lista y alta de notas del expediente (compartido entre tab de detalle y sheet del listado).
 */
export function ExpedienteNotasPanel({
  expedienteId,
  enabled = true,
  compact = false,
  className,
}: ExpedienteNotasPanelProps) {
  const queryClient = useQueryClient();
  const [contenido, setContenido] = useState('');
  const [error, setError] = useState<string | null>(null);

  const { data: notas = [], isLoading } = useQuery({
    queryKey: ['expediente-notas', expedienteId],
    queryFn: () => api.getExpedienteNotas(expedienteId),
    enabled: enabled && !!expedienteId,
  });

  const invalidate = async () => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: ['expediente-notas', expedienteId] }),
      queryClient.invalidateQueries({ queryKey: ['expedientes'] }),
    ]);
  };

  const crearMutation = useMutation({
    mutationFn: (text: string) => api.crearExpedienteNota(expedienteId, text),
    onSuccess: async () => {
      setContenido('');
      setError(null);
      await invalidate();
    },
    onError: (err: Error) => {
      setError(err.message || 'No se pudo guardar la nota.');
    },
  });

  const archivarMutation = useMutation({
    mutationFn: ({ notaId, archivar }: { notaId: string; archivar: boolean }) =>
      archivar
        ? api.archivarExpedienteNota(expedienteId, notaId)
        : api.desarchivarExpedienteNota(expedienteId, notaId),
    onSuccess: async () => {
      await invalidate();
    },
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    const text = contenido.trim();
    if (!text) {
      setError('Escribe el contenido de la nota.');
      return;
    }
    crearMutation.mutate(text);
  };

  const formId = compact ? 'nueva-nota-sheet' : 'nueva-nota-tab';

  return (
    <div className={cn(compact ? 'flex min-h-0 flex-1 flex-col' : 'panel', className)}>
      {!compact ? (
        <div className="panel-header">
          <span className="panel-header-icon">
            <NotebookPen className="h-5 w-5" />
          </span>
          <div>
            <h2 className="panel-title">Notas</h2>
            <p className="text-sm text-muted-foreground">
              Tareas y recordatorios del expediente (más recientes primero).
            </p>
          </div>
        </div>
      ) : null}

      <form
        onSubmit={handleSubmit}
        className={cn('space-y-2 border-b', compact ? 'px-5 py-4' : 'px-5 py-4')}
      >
        <label htmlFor={formId} className="section-label">
          Nueva nota
        </label>
        <textarea
          id={formId}
          value={contenido}
          onChange={(e) => {
            setContenido(e.target.value);
            if (error) setError(null);
          }}
          rows={compact ? 3 : 4}
          maxLength={5000}
          placeholder="Añade una tarea o recordatorio…"
          className="input-field min-h-[5rem] w-full resize-y"
        />
        {error ? <p className="text-sm text-destructive">{error}</p> : null}
        <div className="flex justify-end">
          <Button type="submit" size="sm" disabled={crearMutation.isPending}>
            {crearMutation.isPending ? 'Guardando…' : 'Añadir nota'}
          </Button>
        </div>
      </form>

      <div className={cn(compact ? 'min-h-0 flex-1 overflow-y-auto px-5 py-4' : 'p-5')}>
        {isLoading ? (
          <p className="text-sm text-muted-foreground">Cargando notas…</p>
        ) : notas.length === 0 ? (
          <p className="text-sm text-muted-foreground">Aún no hay notas en este expediente.</p>
        ) : (
          <ul className="space-y-3">
            {notas.map((nota) => (
              <NotaItem
                key={nota.id}
                nota={nota}
                busy={archivarMutation.isPending}
                onToggleArchivo={() =>
                  archivarMutation.mutate({
                    notaId: nota.id,
                    archivar: !nota.archivada,
                  })
                }
              />
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}

function NotaItem({
  nota,
  busy,
  onToggleArchivo,
}: {
  nota: ExpedienteNotaResponse;
  busy: boolean;
  onToggleArchivo: () => void;
}) {
  return (
    <li
      className={cn(
        'rounded-lg border border-border p-3',
        nota.archivada && 'bg-muted/40 opacity-80',
      )}
    >
      <div className="flex items-start justify-between gap-2">
        <p
          className={cn(
            'whitespace-pre-wrap text-sm leading-snug text-foreground',
            nota.archivada && 'line-through text-muted-foreground',
          )}
        >
          {nota.contenido}
        </p>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          className="h-8 w-8 shrink-0 p-0"
          title={nota.archivada ? 'Reabrir nota' : 'Archivar (hecha)'}
          disabled={busy}
          onClick={onToggleArchivo}
        >
          {nota.archivada ? (
            <ArchiveRestore className="h-4 w-4" />
          ) : (
            <Archive className="h-4 w-4" />
          )}
        </Button>
      </div>
      <p className="mt-2 text-[11px] text-muted-foreground">
        {formatFechaNota(nota.createdAt)}
        {nota.archivada ? ' · Archivada' : ''}
      </p>
    </li>
  );
}
