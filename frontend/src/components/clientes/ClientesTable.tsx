import { useMemo, useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
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
import { ChevronLeft, ChevronRight, ChevronsUpDown, Pencil, RefreshCw, Trash2 } from 'lucide-react';
import type { ClienteHoldedEstado, ClienteResponse } from '@/api/client';
import { ClienteHoldedBadge } from '@/components/clientes/ClienteHoldedBadge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
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
  deletingId?: string | null;
  onRefresh?: () => void;
  onDelete?: (id: string) => void;
}

export function ClientesTable({
  data,
  isLoading,
  isFetching,
  deletingId,
  onRefresh,
  onDelete,
}: ClientesTableProps) {
  const navigate = useNavigate();
  const [sorting, setSorting] = useState<SortingState>([{ id: 'nombre', desc: false }]);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [globalFilter, setGlobalFilter] = useState('');
  const [holdedFilter, setHoldedFilter] = useState('');
  const [deleteTarget, setDeleteTarget] = useState<ClienteResponse | null>(null);

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
        id: 'acciones',
        header: 'Acciones',
        enableSorting: false,
        cell: ({ row }) => {
          const busy = deletingId === row.original.id;
          const hasExpedientes = (row.original.numExpedientes ?? 0) > 0;

          return (
            <div className="flex items-center justify-end gap-1">
              <button
                type="button"
                className="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:opacity-40"
                aria-label="Editar cliente"
                title="Editar cliente"
                onClick={() =>
                  navigate({ to: '/clientes/$clienteId', params: { clienteId: row.original.id } })
                }
                disabled={busy}
              >
                <Pencil className="h-4 w-4" />
              </button>
              <button
                type="button"
                className="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-destructive disabled:cursor-not-allowed disabled:opacity-40"
                aria-label="Eliminar cliente"
                title={
                  hasExpedientes
                    ? 'No se puede eliminar un cliente con expedientes'
                    : 'Eliminar cliente'
                }
                onClick={() => setDeleteTarget(row.original)}
                disabled={busy || hasExpedientes || !onDelete}
              >
                <Trash2 className="h-4 w-4" />
              </button>
            </div>
          );
        },
      },
    ],
    [deletingId, navigate, onDelete],
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

  const confirmDelete = () => {
    if (deleteTarget && onDelete) {
      onDelete(deleteTarget.id);
      setDeleteTarget(null);
    }
  };

  return (
    <>
      <div className="panel">
        <div className="table-toolbar">
          <Input
            placeholder="Buscar por nombre, documento, teléfono o email…"
            value={globalFilter}
            onChange={(e) => setGlobalFilter(e.target.value)}
            className="h-9 max-w-sm min-w-[200px] flex-1"
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
                      <TableHead
                        key={header.id}
                        className={cn(header.column.id === 'acciones' && 'text-right')}
                      >
                        {header.isPlaceholder ? null : header.column.getCanSort() ? (
                          <button
                            type="button"
                            className={cn(
                              'flex items-center gap-1',
                              header.column.id === 'acciones' && 'ml-auto',
                            )}
                            onClick={header.column.getToggleSortingHandler()}
                          >
                            {flexRender(header.column.columnDef.header, header.getContext())}
                            <ChevronsUpDown className="h-3 w-3 text-muted-foreground" />
                          </button>
                        ) : (
                          flexRender(header.column.columnDef.header, header.getContext())
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
                        <TableCell
                          key={cell.id}
                          className={cn(cell.column.id === 'acciones' && 'text-right')}
                        >
                          {flexRender(cell.column.columnDef.cell, cell.getContext())}
                        </TableCell>
                      ))}
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>

            <div className="flex items-center justify-between border-t px-4 py-3 text-sm text-muted-foreground">
              <span>{table.getFilteredRowModel().rows.length} cliente(s)</span>
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

      <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Eliminar cliente</DialogTitle>
            <DialogDescription>
              ¿Eliminar «{deleteTarget?.nombre || 'este cliente'}»? Esta acción no se puede deshacer.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => setDeleteTarget(null)}>
              Cancelar
            </Button>
            <Button type="button" variant="destructive" onClick={confirmDelete}>
              Eliminar
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
