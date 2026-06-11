import { useMemo, useState } from 'react';
import { Link } from '@tanstack/react-router';
import {
  type ColumnDef,
  type ColumnFiltersState,
  type SortingState,
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from '@tanstack/react-table';
import { ChevronLeft, ChevronRight, ChevronsUpDown, Eye, RefreshCw } from 'lucide-react';
import type { ClienteHoldedEstado, ClienteResponse } from '@/api/client';
import { ClienteHoldedBadge } from '@/components/clientes/ClienteHoldedBadge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

interface ClientesTableProps {
  data: ClienteResponse[];
  isLoading?: boolean;
  isFetching?: boolean;
  onRefresh?: () => void;
}

export function ClientesTable({ data, isLoading, isFetching, onRefresh }: ClientesTableProps) {
  const [sorting, setSorting] = useState<SortingState>([{ id: 'nombre', desc: false }]);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [globalFilter, setGlobalFilter] = useState('');
  const [holdedFilter, setHoldedFilter] = useState('');

  const filteredData = useMemo(() => {
    if (!holdedFilter) return data;
    return data.filter((c) => (c.holdedEstado ?? 'oportunidad') === holdedFilter);
  }, [data, holdedFilter]);

  const columns = useMemo<ColumnDef<ClienteResponse>[]>(
    () => [
      {
        accessorKey: 'nombre',
        header: 'Nombre',
        cell: ({ row }) => (
          <div>
            <p className="font-medium">{row.original.nombre || '—'}</p>
            <p className="text-xs text-muted-foreground">
              {row.original.tipoDocumento} {row.original.numDocumento}
            </p>
          </div>
        ),
      },
      {
        accessorKey: 'telefono',
        header: 'Contacto',
        cell: ({ row }) => (
          <div className="text-sm">
            <p>{row.original.telefono || '—'}</p>
            <p className="text-xs text-muted-foreground">{row.original.email || '—'}</p>
          </div>
        ),
      },
      {
        accessorKey: 'numExpedientes',
        header: 'Expedientes',
        cell: ({ row }) => (
          <Badge variant="secondary">{row.original.numExpedientes ?? 0}</Badge>
        ),
      },
      {
        accessorKey: 'holdedEstado',
        header: 'Holded',
        cell: ({ row }) => (
          <ClienteHoldedBadge
            estado={(row.original.holdedEstado ?? 'oportunidad') as ClienteHoldedEstado}
            label={row.original.holdedEstadoLabel}
          />
        ),
      },
      {
        id: 'actions',
        header: '',
        cell: ({ row }) => (
          <Button variant="ghost" size="sm" asChild>
            <Link to="/clientes/$clienteId" params={{ clienteId: row.original.id }}>
              <Eye className="mr-2 h-4 w-4" />
              Ver ficha
            </Link>
          </Button>
        ),
      },
    ],
    [],
  );

  const table = useReactTable({
    data: filteredData,
    columns,
    state: { sorting, columnFilters, globalFilter },
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onGlobalFilterChange: setGlobalFilter,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: { pagination: { pageSize: 15 } },
  });

  return (
    <div className="panel">
      <div className="table-toolbar">
        <Input
          placeholder="Buscar por nombre, documento, teléfono o email…"
          value={globalFilter}
          onChange={(e) => setGlobalFilter(e.target.value)}
          className="max-w-sm"
        />
        <select
          className="input-field h-9 w-44"
          value={holdedFilter}
          onChange={(e) => setHoldedFilter(e.target.value)}
        >
          <option value="">Todos (Holded)</option>
          <option value="oportunidad">Oportunidades</option>
          <option value="sincronizado">Sincronizados</option>
          <option value="error">Con error</option>
        </select>
        {onRefresh && (
          <Button variant="outline" size="sm" onClick={onRefresh} disabled={isFetching}>
            <RefreshCw className={cn('h-4 w-4', isFetching && 'animate-spin')} />
          </Button>
        )}
      </div>

      {isLoading ? (
        <p className="p-6 text-sm text-muted-foreground">Cargando clientes…</p>
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
                    No hay clientes que coincidan con los filtros.
                  </TableCell>
                </TableRow>
              ) : (
                table.getRowModel().rows.map((row) => (
                  <TableRow key={row.id}>
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
            <span>
              {table.getFilteredRowModel().rows.length} cliente(s)
            </span>
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
