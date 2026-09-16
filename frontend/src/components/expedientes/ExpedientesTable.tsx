import { useMemo, useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import {
  type ColumnDef,
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from '@tanstack/react-table';
import { ChevronLeft, ChevronRight, ChevronsUpDown, Info, RefreshCw } from 'lucide-react';
import type { ExpedienteResponse } from '@/api/client';
import { ConfigListToolbar } from '@/components/config/ConfigListToolbar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { labelFaseNegocio } from '@/lib/portal-fases';
import { capitalizeDisplay } from '@/lib/capitalize-display';
import { ExpedienteCobrosBadge } from '@/components/expedientes/ExpedienteCobrosBadge';
import { ExpedienteNotasCell } from '@/components/expedientes/ExpedienteNotasCell';
import { ExpedienteNotasSheet } from '@/components/expedientes/ExpedienteNotasSheet';
import { ExpedienteSubfaseBadge } from '@/components/expedientes/ExpedienteSubfaseBadge';
import { ExpedienteVencimientoBadge } from '@/components/expedientes/ExpedienteVencimientoBadge';
import {
  labelEstadoExpediente,
  normalizarEstadoFiltro,
  variantEstadoExpediente,
} from '@/lib/expediente-estado';
import { formatEuros } from '@/lib/pago-contratacion';
import { proximoVencimiento, tienePlazoUrgente, tienePlazoVencido } from '@/lib/vencimiento-proximo';
import { cn } from '@/lib/utils';

function importesCobro(exp: ExpedienteResponse): { cobrado: number; total: number } | null {
  const resumen = exp.resumenCobros;
  if (resumen && resumen.importeTotal > 0) {
    return { cobrado: resumen.cobrado, total: resumen.importeTotal };
  }
  const honorarios = exp.honorariosAcordados ?? 0;
  if (honorarios > 0) {
    return { cobrado: resumen?.cobrado ?? 0, total: honorarios };
  }
  return null;
}

interface ExpedientesTableProps {
  data: ExpedienteResponse[];
  isLoading?: boolean;
  isFetching?: boolean;
  onRefresh?: () => void;
}

function formatFechaAlta(iso: string | null | undefined): string {
  if (!iso) return '—';
  try {
    return new Date(iso).toLocaleDateString('es-ES', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    });
  } catch {
    return '—';
  }
}

function matchesAtencionFilter(exp: ExpedienteResponse, atencionFilter: string[]): boolean {
  if (atencionFilter.length === 0) return true;
  return atencionFilter.some((value) => {
    if (value === 'avisos') return (exp.avisosPendientes ?? 0) > 0;
    if (value === 'vencidos') return (exp.resumenCobros?.vencidas ?? 0) > 0;
    if (value === 'plazo_vencido') return tienePlazoVencido(exp);
    if (value === 'plazo_urgente') return tienePlazoUrgente(exp);
    if (value === 'cobro_pendiente') {
      return exp.paymentStatus === 'pending' || exp.paymentStatus === 'partial';
    }
    return false;
  });
}

function matchesSubfaseFilter(exp: ExpedienteResponse, subfaseFilter: string[]): boolean {
  if (subfaseFilter.length === 0) return true;

  return subfaseFilter.some((value) => {
    if (value.startsWith('contratacion:')) {
      const codigo = value.slice('contratacion:'.length);
      return (
        exp.faseNegocio === 'contratacion' && exp.subfaseContratacion?.codigo === codigo
      );
    }
    if (value.startsWith('tramitacion:')) {
      const codigo = value.slice('tramitacion:'.length);
      return exp.faseNegocio === 'tramitacion' && exp.subfaseTramitacion === codigo;
    }
    if (value === 'documentacion:pendientes') {
      return (
        exp.faseNegocio === 'documentacion' && (exp.subfaseDocumentacion?.pendientes ?? 0) > 0
      );
    }
    if (value === 'documentacion:revision') {
      return (
        exp.faseNegocio === 'documentacion' && (exp.subfaseDocumentacion?.enRevision ?? 0) > 0
      );
    }
    return false;
  });
}

export function ExpedientesTable({ data, isLoading, isFetching, onRefresh }: ExpedientesTableProps) {
  const navigate = useNavigate();
  const [globalFilter, setGlobalFilter] = useState('');
  const [estadoFilter, setEstadoFilter] = useState<string[]>(['abierto']);
  const [faseFilter, setFaseFilter] = useState<string[]>([]);
  const [cobroFilter, setCobroFilter] = useState<string[]>([]);
  const [atencionFilter, setAtencionFilter] = useState<string[]>([]);
  const [metodoPagoFilter, setMetodoPagoFilter] = useState<string[]>([]);
  const [subfaseFilter, setSubfaseFilter] = useState<string[]>([]);
  const [notasExpediente, setNotasExpediente] = useState<ExpedienteResponse | null>(null);

  const filteredData = useMemo(() => {
    return data.filter((exp) => {
      if (
        estadoFilter.length > 0 &&
        !estadoFilter.includes(normalizarEstadoFiltro(exp.estado))
      ) {
        return false;
      }
      if (faseFilter.length > 0 && (!exp.faseNegocio || !faseFilter.includes(exp.faseNegocio))) {
        return false;
      }
      if (cobroFilter.length > 0 && !cobroFilter.includes(exp.paymentStatus)) return false;
      if (!matchesAtencionFilter(exp, atencionFilter)) return false;
      if (
        metodoPagoFilter.length > 0 &&
        (!exp.metodoPago || !metodoPagoFilter.includes(exp.metodoPago))
      ) {
        return false;
      }
      if (!matchesSubfaseFilter(exp, subfaseFilter)) return false;
      return true;
    });
  }, [
    data,
    estadoFilter,
    faseFilter,
    cobroFilter,
    atencionFilter,
    metodoPagoFilter,
    subfaseFilter,
  ]);

  const columns = useMemo<ColumnDef<ExpedienteResponse>[]>(
    () => [
      {
        accessorKey: 'numero',
        header: 'Nº',
        cell: ({ row }) => (
          <span className="font-mono text-muted-foreground">{row.original.numero}</span>
        ),
      },
      {
        accessorKey: 'titulo',
        header: 'Título',
        cell: ({ row }) => (
          <span className="font-medium">{capitalizeDisplay(row.original.titulo)}</span>
        ),
      },
      {
        accessorKey: 'clientName',
        header: 'Cliente',
        cell: ({ row }) => {
          const raw = row.original.clientName?.trim() ?? '';
          const label =
            !raw || raw === 'Cliente pendiente'
              ? 'Pendiente de identificación'
              : /^\+?[\d\s\-().]{9,}$/.test(raw)
                ? raw
                : capitalizeDisplay(raw);
          return <span className="text-muted-foreground">{label}</span>;
        },
      },
      {
        accessorKey: 'faseNegocio',
        header: 'Fase',
        cell: ({ row }) =>
          row.original.faseNegocio ? (
            <Badge variant="info">{labelFaseNegocio(row.original.faseNegocio)}</Badge>
          ) : (
            <span className="text-muted-foreground">—</span>
          ),
      },
      {
        id: 'subfase',
        header: 'Subfase',
        cell: ({ row }) => <ExpedienteSubfaseBadge expediente={row.original} />,
      },
      {
        id: 'cobros',
        header: 'Cobros',
        cell: ({ row }) => <ExpedienteCobrosBadge expediente={row.original} />,
      },
      {
        id: 'pagadoTotal',
        header: 'Pagado / Total',
        sortingFn: (a, b) => {
          const ia = importesCobro(a.original);
          const ib = importesCobro(b.original);
          const ratioA = ia ? ia.cobrado / ia.total : -1;
          const ratioB = ib ? ib.cobrado / ib.total : -1;
          if (ratioA !== ratioB) return ratioA - ratioB;
          return (ia?.total ?? 0) - (ib?.total ?? 0);
        },
        cell: ({ row }) => {
          const importes = importesCobro(row.original);
          if (!importes) {
            return <span className="text-muted-foreground">—</span>;
          }
          const completo = importes.cobrado >= importes.total;
          return (
            <span
              className={cn(
                'whitespace-nowrap tabular-nums text-sm',
                completo ? 'text-emerald-700' : 'text-foreground',
              )}
              title={`Pendiente: ${formatEuros(Math.max(0, importes.total - importes.cobrado))}`}
            >
              <span className="font-medium">{formatEuros(importes.cobrado)}</span>
              <span className="text-muted-foreground"> / {formatEuros(importes.total)}</span>
            </span>
          );
        },
      },
      {
        accessorKey: 'estado',
        header: 'Estado',
        cell: ({ row }) => (
          <Badge variant={variantEstadoExpediente(row.original.estado)}>
            {row.original.estadoLabel || labelEstadoExpediente(row.original.estado)}
          </Badge>
        ),
      },
      {
        accessorKey: 'fechaApertura',
        header: 'Alta',
        sortingFn: (a, b) => {
          const fa = a.original.fechaApertura ?? '';
          const fb = b.original.fechaApertura ?? '';
          return fa.localeCompare(fb);
        },
        cell: ({ row }) => (
          <span className="text-muted-foreground">{formatFechaAlta(row.original.fechaApertura)}</span>
        ),
      },
      {
        id: 'proximoVencimiento',
        header: 'Vencimiento',
        sortingFn: (a, b) => {
          const fa = proximoVencimiento(a.original)?.fecha ?? '9999-99-99';
          const fb = proximoVencimiento(b.original)?.fecha ?? '9999-99-99';
          return fa.localeCompare(fb);
        },
        cell: ({ row }) => <ExpedienteVencimientoBadge expediente={row.original} />,
      },
      {
        id: 'avisos',
        accessorKey: 'avisosPendientes',
        header: 'Avisos',
        cell: ({ row }) => {
          const total = row.original.avisosPendientes ?? 0;
          if (total === 0) {
            return <span className="text-muted-foreground">—</span>;
          }

          const detalle = row.original.avisosDetalle;
          const tooltipParts: string[] = [];
          if (detalle?.notificaciones) {
            tooltipParts.push(`Sin leer: ${detalle.notificaciones}`);
          }
          if (detalle?.contratacion) {
            tooltipParts.push(`Contratación: ${detalle.contratacion}`);
          }
          if (detalle?.documentacion) {
            tooltipParts.push(`Documentación: ${detalle.documentacion}`);
          }

          return (
            <Badge
              variant="warning"
              title={tooltipParts.length > 0 ? tooltipParts.join(' · ') : undefined}
            >
              {total} sin leer
            </Badge>
          );
        },
      },
      {
        id: 'notas',
        header: () => (
          <span className="inline-flex items-center gap-1">
            Notas
            <TooltipProvider delayDuration={200}>
              <Tooltip>
                <TooltipTrigger asChild>
                  <span
                    className="inline-flex text-muted-foreground"
                    onClick={(e) => e.stopPropagation()}
                    onKeyDown={(e) => e.stopPropagation()}
                  >
                    <Info className="h-3.5 w-3.5" aria-hidden />
                    <span className="sr-only">Información sobre notas</span>
                  </span>
                </TooltipTrigger>
                <TooltipContent side="top" className="max-w-xs p-2 text-xs">
                  Pasa el ratón sobre el icono de una fila con notas activas para ver la última.
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </span>
        ),
        sortingFn: (a, b) => (a.original.notasActivas ?? 0) - (b.original.notasActivas ?? 0),
        cell: ({ row }) => (
          <ExpedienteNotasCell
            expediente={row.original}
            onOpen={() => setNotasExpediente(row.original)}
          />
        ),
      },
    ],
    [],
  );

  const table = useReactTable({
    data: filteredData,
    columns,
    state: { globalFilter },
    onGlobalFilterChange: setGlobalFilter,
    globalFilterFn: (row, _columnId, filterValue) => {
      const q = String(filterValue).toLowerCase().trim();
      if (!q) return true;
      const exp = row.original;
      return (
        exp.numero.toLowerCase().includes(q) ||
        exp.titulo.toLowerCase().includes(q) ||
        exp.clientName.toLowerCase().includes(q) ||
        exp.caseReference.toLowerCase().includes(q)
      );
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: { pagination: { pageSize: 15 } },
  });

  const notasLabel = notasExpediente
    ? `${notasExpediente.numero} · ${capitalizeDisplay(notasExpediente.titulo)}`
    : '';

  return (
    <div className="panel overflow-hidden">
      <ExpedienteNotasSheet
        expedienteId={notasExpediente?.id ?? ''}
        expedienteLabel={notasLabel}
        open={notasExpediente !== null}
        onOpenChange={(open) => {
          if (!open) setNotasExpediente(null);
        }}
      />
      <ConfigListToolbar
        search={globalFilter}
        onSearchChange={setGlobalFilter}
        searchPlaceholder="Buscar por nº, título, cliente o referencia…"
        selectFilters={[
          {
            id: 'estado',
            label: 'Estado',
            emptyLabel: 'Todos los estados',
            values: estadoFilter,
            onChange: setEstadoFilter,
            options: [
              { value: 'abierto', label: 'Abierto' },
              { value: 'cancelado', label: 'Cancelado' },
              { value: 'archivado', label: 'Archivado' },
            ],
          },
          {
            id: 'fase',
            label: 'Fase',
            emptyLabel: 'Todas las fases',
            values: faseFilter,
            onChange: setFaseFilter,
            options: [
              { value: 'contratacion', label: 'Contratación' },
              { value: 'documentacion', label: 'Documentación' },
              { value: 'tramitacion', label: 'Tramitación' },
              { value: 'resolucion', label: 'Resolución' },
            ],
          },
          {
            id: 'cobro',
            label: 'Cobro',
            emptyLabel: 'Todos los cobros',
            values: cobroFilter,
            onChange: setCobroFilter,
            options: [
              { value: 'pending', label: 'Pendiente' },
              { value: 'partial', label: 'Parcial' },
              { value: 'paid', label: 'Cobrado' },
              { value: 'failed', label: 'Fallido' },
            ],
          },
        ]}
        moreFilters={[
          {
            id: 'atencion',
            label: 'Atención rápida',
            values: atencionFilter,
            onChange: setAtencionFilter,
            options: [
              { value: 'avisos', label: 'Con avisos sin leer' },
              { value: 'plazo_vencido', label: 'Plazo vencido' },
              { value: 'plazo_urgente', label: 'Vence en 7 días o menos' },
              { value: 'vencidos', label: 'Cuotas vencidas' },
              { value: 'cobro_pendiente', label: 'Cobro pendiente o parcial' },
            ],
          },
          {
            id: 'metodoPago',
            label: 'Método de pago',
            values: metodoPagoFilter,
            onChange: setMetodoPagoFilter,
            options: [
              { value: 'manual', label: 'Manual' },
              { value: 'digital', label: 'Digital (Stripe)' },
            ],
          },
          {
            id: 'subfase',
            label: 'Subfase',
            values: subfaseFilter,
            onChange: setSubfaseFilter,
            options: [
              { value: 'contratacion:datos_cliente', label: 'Contratación · Identificación' },
              { value: 'contratacion:firmas', label: 'Contratación · Firmas' },
              { value: 'contratacion:pago', label: 'Contratación · Pago inicial' },
              { value: 'documentacion:pendientes', label: 'Documentación · Pendientes' },
              { value: 'documentacion:revision', label: 'Documentación · En revisión' },
              {
                value: 'tramitacion:pendiente_tramitacion',
                label: 'Tramitación · Pendiente de tramitación',
              },
              { value: 'tramitacion:tramitado', label: 'Tramitación · Tramitado' },
              {
                value: 'tramitacion:pendiente_requerimiento',
                label: 'Tramitación · Pendiente de requerimiento',
              },
            ],
          },
        ]}
        trailing={
          onRefresh ? (
            <Button variant="outline" size="sm" onClick={onRefresh} disabled={isFetching}>
              <RefreshCw className={cn('h-4 w-4', isFetching && 'animate-spin')} />
            </Button>
          ) : undefined
        }
      />

      {isLoading ? (
        <p className="p-6 text-sm text-muted-foreground">Cargando expedientes…</p>
      ) : (
        <>
          <Table>
            <TableHeader>
              {table.getHeaderGroups().map((hg) => (
                <TableRow key={hg.id}>
                  {hg.headers.map((header) => (
                    <TableHead key={header.id}>
                      {header.isPlaceholder ? null : (
                        <button
                          type="button"
                          className="flex items-center gap-1"
                          onClick={header.column.getToggleSortingHandler()}
                        >
                          {flexRender(header.column.columnDef.header, header.getContext())}
                          <ChevronsUpDown className="h-3 w-3 text-muted-foreground" />
                        </button>
                      )}
                    </TableHead>
                  ))}
                </TableRow>
              ))}
            </TableHeader>
            <TableBody>
              {table.getRowModel().rows.length === 0 ? (
                <TableRow>
                  <TableCell
                    colSpan={columns.length}
                    className="py-12 text-center text-muted-foreground"
                  >
                    No hay expedientes que coincidan con los filtros.
                  </TableCell>
                </TableRow>
              ) : (
                table.getRowModel().rows.map((row) => (
                  <TableRow
                    key={row.id}
                    className="cursor-pointer hover:bg-primary/5 transition-colors"
                    title="Doble clic para abrir"
                    onDoubleClick={() =>
                      navigate({
                        to: '/expedientes/$expedienteId',
                        params: { expedienteId: row.original.id },
                      })
                    }
                  >
                    {row.getVisibleCells().map((cell) => (
                      <TableCell key={cell.id}>
                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                      </TableCell>
                    ))}
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>

          <div className="flex items-center justify-between border-t px-4 py-3 text-sm text-muted-foreground">
            <span>{table.getFilteredRowModel().rows.length} expediente(s)</span>
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => table.previousPage()}
                disabled={!table.getCanPreviousPage()}
              >
                <ChevronLeft className="h-4 w-4" />
              </Button>
              <span>
                Página {table.getState().pagination.pageIndex + 1} de {table.getPageCount() || 1}
              </span>
              <Button
                variant="outline"
                size="sm"
                onClick={() => table.nextPage()}
                disabled={!table.getCanNextPage()}
              >
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </>
      )}
    </div>
  );
}
