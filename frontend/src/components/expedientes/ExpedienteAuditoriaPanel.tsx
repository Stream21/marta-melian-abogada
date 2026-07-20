import { useEffect, useMemo, useRef, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import type { ColumnDef } from '@tanstack/react-table';
import { ClipboardList, Loader2 } from 'lucide-react';
import { api, type ExpedienteAuditoriaEntryResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { DataTable, type FilterableColumn } from '@/components/ui/data-table';
import { useMercureContratacion } from '@/hooks/useMercureContratacion';
import { cn } from '@/lib/utils';

interface ExpedienteAuditoriaPanelProps {
  expedienteId: string;
  focusHitoId?: string;
  onFocusConsumed?: () => void;
}

function auditoriaRowDomId(hitoId: string): string {
  return `auditoria-row-${hitoId}`;
}

const CATEGORIA_VARIANT: Record<string, 'info' | 'success' | 'warning' | 'secondary' | 'destructive'> = {
  contratacion: 'info',
  requerimientos: 'info',
  comunicacion: 'warning',
  pago: 'success',
  documento: 'secondary',
  estado: 'info',
  sistema: 'secondary',
};

const ACTOR_VARIANT: Record<string, 'info' | 'success' | 'secondary'> = {
  cliente: 'info',
  abogado: 'success',
  sistema: 'secondary',
};

function formatFecha(iso: string): string {
  return new Date(iso).toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function ExpedienteAuditoriaPanel({
  expedienteId,
  focusHitoId,
  onFocusConsumed,
}: ExpedienteAuditoriaPanelProps) {
  useMercureContratacion(expedienteId);
  const focusHandled = useRef<string | null>(null);
  const [highlightedRowDomId, setHighlightedRowDomId] = useState<string | undefined>();

  const { data, isLoading, error } = useQuery({
    queryKey: ['expediente-auditoria', expedienteId],
    queryFn: () => api.getExpedienteAuditoria(expedienteId),
    staleTime: 5000,
  });

  const columns = useMemo<ColumnDef<ExpedienteAuditoriaEntryResponse>[]>(
    () => [
      {
        accessorKey: 'createdAt',
        header: 'Fecha',
        cell: ({ row }) => (
          <span className="whitespace-nowrap text-sm tabular-nums text-muted-foreground">
            {formatFecha(row.original.createdAt)}
          </span>
        ),
        sortingFn: 'datetime',
      },
      {
        accessorKey: 'categoria',
        header: 'Categoría',
        cell: ({ row }) => (
          <Badge variant={CATEGORIA_VARIANT[row.original.categoria] ?? 'secondary'}>
            {row.original.categoriaLabel}
          </Badge>
        ),
        filterFn: 'equals',
      },
      {
        accessorKey: 'tipoLabel',
        header: 'Tipo',
        cell: ({ row }) => <span className="text-sm font-medium">{row.original.tipoLabel}</span>,
      },
      {
        accessorKey: 'actor',
        header: 'Actor',
        cell: ({ row }) => (
          <Badge variant={ACTOR_VARIANT[row.original.actor] ?? 'secondary'} className="capitalize">
            {row.original.actorLabel}
          </Badge>
        ),
        filterFn: 'equals',
      },
      {
        accessorKey: 'canal',
        header: 'Canal',
        cell: ({ row }) =>
          row.original.canalLabel ? (
            <span className="text-sm">{row.original.canalLabel}</span>
          ) : (
            <span className="text-sm text-muted-foreground">—</span>
          ),
        filterFn: (row, _columnId, filterValue) => {
          if (!filterValue) return true;
          return row.original.canal === filterValue;
        },
      },
      {
        accessorKey: 'resumen',
        header: 'Descripción',
        cell: ({ row }) => (
          <div className="max-w-xl">
            <p className="text-sm">{row.original.resumen}</p>
            {row.original.detalle && row.original.detalle !== row.original.resumen && (
              <p className="mt-1 text-xs text-muted-foreground line-clamp-2" title={row.original.detalle}>
                {row.original.detalle}
              </p>
            )}
          </div>
        ),
      },
    ],
    [],
  );

  const filterableColumns = useMemo<FilterableColumn[]>(() => {
    const items = data?.items ?? [];
    const categorias = [...new Set(items.map((i) => i.categoria))];
    const actores = [...new Set(items.map((i) => i.actor))];
    const canales = [...new Set(items.map((i) => i.canal).filter(Boolean))] as string[];

    return [
      {
        id: 'categoria',
        title: 'Categoría',
        options: categorias.map((c) => ({
          value: c,
          label: items.find((i) => i.categoria === c)?.categoriaLabel ?? c,
        })),
      },
      {
        id: 'actor',
        title: 'Actor',
        options: actores.map((a) => ({
          value: a,
          label: items.find((i) => i.actor === a)?.actorLabel ?? a,
        })),
      },
      ...(canales.length > 0
        ? [
            {
              id: 'canal',
              title: 'Canal',
              options: canales.map((c) => ({
                value: c,
                label: items.find((i) => i.canal === c)?.canalLabel ?? c,
              })),
            } satisfies FilterableColumn,
          ]
        : []),
    ];
  }, [data?.items]);

  const focusPageIndex = useMemo(() => {
    if (!focusHitoId || !data?.items.length) return undefined;
    const index = data.items.findIndex((item) => item.id === focusHitoId);
    if (index < 0) return undefined;
    return Math.floor(index / 15);
  }, [focusHitoId, data?.items]);

  useEffect(() => {
    if (!focusHitoId || !data?.items.length || focusHandled.current === focusHitoId) {
      return;
    }

    const rowDomId = auditoriaRowDomId(focusHitoId);
    const scrollToRow = () => {
      const element = document.getElementById(rowDomId);
      if (!element) return;

      element.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setHighlightedRowDomId(rowDomId);
      window.setTimeout(() => setHighlightedRowDomId(undefined), 2500);
      focusHandled.current = focusHitoId;
      onFocusConsumed?.();
    };

    window.requestAnimationFrame(() => {
      window.setTimeout(scrollToRow, 80);
    });
  }, [data?.items, focusHitoId, focusPageIndex, onFocusConsumed]);

  if (isLoading) {
    return (
      <div className="panel flex items-center justify-center gap-2 p-12 text-muted-foreground">
        <Loader2 className="h-5 w-5 animate-spin" />
        Cargando auditoría…
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="panel p-8 text-center text-destructive">
        No se pudo cargar la auditoría del expediente.
      </div>
    );
  }

  return (
    <div className="panel overflow-hidden">
      <div className="flex flex-wrap items-start justify-between gap-4 border-b border-border p-6">
        <div className="flex items-start gap-3">
          <div className="panel-header-icon">
            <ClipboardList className="h-5 w-5" />
          </div>
          <div>
            <h2 className="panel-title">Auditoría del expediente</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              Historial unificado de gestiones, comunicaciones, pagos y cambios de estado.
            </p>
          </div>
        </div>
        <Badge variant="secondary" className={cn('shrink-0')}>
          {data.total} evento{data.total !== 1 ? 's' : ''}
        </Badge>
      </div>

      <DataTable
        columns={columns}
        data={data.items}
        filterableColumns={filterableColumns}
        searchPlaceholder="Buscar en descripción, tipo o actor…"
        pageSize={15}
        initialPageIndex={focusPageIndex}
        getRowDomId={(row) => (row.source === 'hito' ? auditoriaRowDomId(row.id) : undefined)}
        highlightedRowDomId={highlightedRowDomId}
      />
    </div>
  );
}
