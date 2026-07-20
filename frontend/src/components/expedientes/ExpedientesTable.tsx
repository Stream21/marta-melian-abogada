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
import { ChevronLeft, ChevronRight, ChevronsUpDown, RefreshCw } from 'lucide-react';
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
import { labelFaseNegocio } from '@/lib/portal-fases';
import { cn } from '@/lib/utils';

interface ExpedientesTableProps {
  data: ExpedienteResponse[];
  isLoading?: boolean;
  isFetching?: boolean;
  onRefresh?: () => void;
}

export function ExpedientesTable({ data, isLoading, isFetching, onRefresh }: ExpedientesTableProps) {
  const navigate = useNavigate();
  const [globalFilter, setGlobalFilter] = useState('');
  const [estadoFilter, setEstadoFilter] = useState<string[]>(['abierto']);
  const [faseFilter, setFaseFilter] = useState<string[]>([]);
  const [cobroFilter, setCobroFilter] = useState<string[]>([]);
  const [avisosFilter, setAvisosFilter] = useState<string[]>([]);

  const filteredData = useMemo(() => {
    return data.filter((exp) => {
      if (estadoFilter.length > 0 && !estadoFilter.includes(exp.estado)) return false;
      if (faseFilter.length > 0 && (!exp.faseNegocio || !faseFilter.includes(exp.faseNegocio))) return false;
      if (cobroFilter.length > 0 && !cobroFilter.includes(exp.paymentStatus)) return false;
      if (avisosFilter.includes('pendientes') && (exp.avisosPendientes ?? 0) === 0) return false;
      return true;
    });
  }, [data, estadoFilter, faseFilter, cobroFilter, avisosFilter]);

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
        cell: ({ row }) => <span className="font-medium">{row.original.titulo}</span>,
      },
      {
        accessorKey: 'clientName',
        header: 'Cliente',
        cell: ({ row }) => (
          <span className="text-muted-foreground">{row.original.clientName}</span>
        ),
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
        accessorKey: 'avisosPendientes',
        header: 'Avisos',
        cell: ({ row }) => {
          const total = row.original.avisosPendientes ?? 0;
          if (total === 0) {
            return <span className="text-muted-foreground">—</span>;
          }

          const detalle = row.original.avisosDetalle;
          const tooltipParts: string[] = [];
          if (detalle?.contratacion) {
            tooltipParts.push(`Contratación: ${detalle.contratacion}`);
          }
          if (detalle?.requerimientos) {
            tooltipParts.push(`Requerimientos: ${detalle.requerimientos}`);
          }

          return (
            <Badge
              variant="warning"
              title={tooltipParts.length > 0 ? tooltipParts.join(' · ') : undefined}
            >
              {total} pendiente{total !== 1 ? 's' : ''}
            </Badge>
          );
        },
      },
      {
        accessorKey: 'estado',
        header: 'Estado',
        cell: ({ row }) => (
          <Badge variant={row.original.estado === 'abierto' ? 'default' : 'secondary'}>
            {row.original.estado}
          </Badge>
        ),
      },
      {
        accessorKey: 'fechaApertura',
        header: 'Fecha',
        cell: ({ row }) => (
          <span className="text-muted-foreground">
            {new Date(row.original.fechaApertura).toLocaleDateString('es-ES')}
          </span>
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


  return (
    <div className="panel overflow-hidden">
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
              { value: 'cerrado', label: 'Cerrado' },
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
              { value: 'requerimientos', label: 'Requerimientos' },
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
              { value: 'paid', label: 'Cobrado' },
              { value: 'failed', label: 'Fallido' },
            ],
          },
          {
            id: 'avisos',
            label: 'Avisos',
            emptyLabel: 'Todos',
            values: avisosFilter,
            onChange: setAvisosFilter,
            options: [{ value: 'pendientes', label: 'Con avisos pendientes' }],
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
                  <TableCell colSpan={columns.length} className="py-12 text-center text-muted-foreground">
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
