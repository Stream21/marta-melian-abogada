import { useMemo, useState } from 'react';
import {
  type ColumnDef,
  type ColumnFiltersState,
  type Row,
  type SortingState,
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from '@tanstack/react-table';
import {
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  ChevronUp,
  ChevronsUpDown,
  Pencil,
  RefreshCw,
  Trash2,
} from 'lucide-react';
import type { TipoCasoResponse } from '@/api/client';
import { cn } from '@/lib/utils';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

function SortIcon({ sorted }: { sorted: false | 'asc' | 'desc' }) {
  if (sorted === 'asc') return <ChevronUp className="ml-1.5 h-3.5 w-3.5 shrink-0" />;
  if (sorted === 'desc') return <ChevronDown className="ml-1.5 h-3.5 w-3.5 shrink-0" />;
  return <ChevronsUpDown className="ml-1.5 h-3.5 w-3.5 shrink-0 opacity-30" />;
}

function nombreFilterFn(row: Row<TipoCasoResponse>, _columnId: string, filterValue: unknown) {
  const q = String(filterValue ?? '').trim().toLowerCase();
  if (!q) return true;
  return row.original.nombre.toLowerCase().includes(q);
}

function descripcionFilterFn(row: Row<TipoCasoResponse>, _columnId: string, filterValue: unknown) {
  const q = String(filterValue ?? '').trim().toLowerCase();
  if (!q) return true;
  return row.original.descripcion.toLowerCase().includes(q);
}

export interface TiposCasoTableProps {
  data: TipoCasoResponse[];
  isLoading?: boolean;
  onRefresh?: () => void;
  isRefreshing?: boolean;
  onEdit: (id: string) => void;
  onDelete: (id: string) => void;
  deletingId?: string | null;
}

export function TiposCasoTable({
  data,
  isLoading,
  onRefresh,
  isRefreshing = false,
  onEdit,
  onDelete,
  deletingId,
}: TiposCasoTableProps) {
  const [sorting, setSorting] = useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [deleteTarget, setDeleteTarget] = useState<TipoCasoResponse | null>(null);

  const columns = useMemo<ColumnDef<TipoCasoResponse>[]>(
    () => [
      {
        id: 'nombre',
        accessorKey: 'nombre',
        header: 'Nombre del tipo de caso',
        filterFn: nombreFilterFn,
        cell: ({ row }) => (
          <span className="font-semibold text-foreground">{row.original.nombre}</span>
        ),
      },
      {
        id: 'descripcion',
        accessorKey: 'descripcion',
        header: 'Descripción',
        filterFn: descripcionFilterFn,
        cell: ({ getValue }) => (
          <p className="max-w-md text-sm text-muted-foreground line-clamp-2">{getValue<string>()}</p>
        ),
      },
      {
        id: 'acciones',
        header: 'Acciones',
        enableSorting: false,
        enableColumnFilter: false,
        cell: ({ row }) => {
          const busy = deletingId === row.original.id;
          return (
            <div className="flex items-center justify-end gap-1">
              <button
                type="button"
                className="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:opacity-40"
                aria-label="Editar"
                onClick={() => onEdit(row.original.id)}
                disabled={busy}
              >
                <Pencil className="h-4 w-4" />
              </button>
              <button
                type="button"
                className="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-destructive disabled:opacity-40"
                aria-label="Eliminar"
                onClick={() => setDeleteTarget(row.original)}
                disabled={busy}
              >
                <Trash2 className="h-4 w-4" />
              </button>
            </div>
          );
        },
      },
    ],
    [onEdit, deletingId],
  );

  const table = useReactTable({
    data,
    columns,
    state: { sorting, columnFilters },
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: { pagination: { pageSize: 10 } },
  });

  const filteredCount = table.getFilteredRowModel().rows.length;
  const pageIndex = table.getState().pagination.pageIndex;
  const pageSize = table.getState().pagination.pageSize;
  const pageCount = table.getPageCount();

  const from = filteredCount === 0 ? 0 : pageIndex * pageSize + 1;
  const to = Math.min((pageIndex + 1) * pageSize, filteredCount);

  const nombreCol = table.getColumn('nombre');
  const descripcionCol = table.getColumn('descripcion');

  const confirmDelete = () => {
    if (deleteTarget) {
      onDelete(deleteTarget.id);
      setDeleteTarget(null);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-16 text-sm text-muted-foreground">
        Cargando tipos de caso…
      </div>
    );
  }

  return (
    <>
      <div className="flex flex-col overflow-hidden">
        <div className="flex-1 overflow-auto">
          <Table>
            <TableHeader>
              {table.getHeaderGroups().map((hg) => (
                <TableRow key={hg.id} className="border-b bg-muted/50 hover:bg-muted/50">
                  {hg.headers.map((header) => (
                    <TableHead
                      key={header.id}
                      className={cn(
                        'px-6 py-3.5 section-label',
                        header.column.id === 'acciones' && 'text-right',
                      )}
                    >
                      {header.isPlaceholder ? null : header.column.getCanSort() ? (
                        <button
                          type="button"
                          onClick={header.column.getToggleSortingHandler()}
                          className="flex items-center transition-colors hover:text-foreground"
                        >
                          {flexRender(header.column.columnDef.header, header.getContext())}
                          <SortIcon sorted={header.column.getIsSorted()} />
                        </button>
                      ) : (
                        flexRender(header.column.columnDef.header, header.getContext())
                      )}
                    </TableHead>
                  ))}
                </TableRow>
              ))}
              <TableRow className="border-b bg-card hover:bg-card">
                <TableHead className="px-6 py-2 align-top">
                  <input
                    type="text"
                    value={(nombreCol?.getFilterValue() as string) ?? ''}
                    onChange={(e) => nombreCol?.setFilterValue(e.target.value || undefined)}
                    placeholder="Filtrar…"
                    className="input-field px-2.5 py-1.5 text-xs"
                    aria-label="Filtrar por nombre"
                  />
                </TableHead>
                <TableHead className="px-6 py-2 align-top">
                  <input
                    type="text"
                    value={(descripcionCol?.getFilterValue() as string) ?? ''}
                    onChange={(e) => descripcionCol?.setFilterValue(e.target.value || undefined)}
                    placeholder="Filtrar…"
                    className="input-field px-2.5 py-1.5 text-xs"
                    aria-label="Filtrar por descripción"
                  />
                </TableHead>
                <TableHead
                  className={cn('px-6 py-2 align-top', 'text-right')}
                  aria-hidden
                />
              </TableRow>
            </TableHeader>
            <TableBody>
              {table.getRowModel().rows.length ? (
                table.getRowModel().rows.map((row) => (
                  <TableRow key={row.id} className="group border-b transition-colors hover:bg-primary/5">
                    {row.getVisibleCells().map((cell) => (
                      <TableCell
                        key={cell.id}
                        className={cn(
                          'px-6 py-4',
                          cell.column.id === 'acciones' && 'text-right',
                        )}
                      >
                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                      </TableCell>
                    ))}
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={columns.length} className="h-32 text-center text-sm text-muted-foreground">
                    No se encontraron resultados.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>

        <div className="flex flex-wrap items-center justify-between gap-3 border-t bg-muted/50 px-6 py-3.5 text-sm text-muted-foreground">
          <span>
            Mostrando {from} a {to} de {filteredCount} registro{filteredCount !== 1 ? 's' : ''}
          </span>
          <div className="flex flex-wrap items-center gap-1">
            {onRefresh && (
              <button
                type="button"
                onClick={onRefresh}
                disabled={isRefreshing}
                className="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-40"
                aria-label="Actualizar listado"
                title="Actualizar listado"
              >
                <RefreshCw className={cn('h-4 w-4', isRefreshing && 'animate-spin')} />
              </button>
            )}
            <button
              type="button"
              onClick={() => table.previousPage()}
              disabled={!table.getCanPreviousPage()}
              className="rounded-lg p-1.5 transition-colors hover:bg-muted disabled:cursor-not-allowed disabled:opacity-30"
              aria-label="Página anterior"
            >
              <ChevronLeft className="h-4 w-4" />
            </button>
            {Array.from({ length: pageCount }, (_, i) => (
              <button
                key={i}
                type="button"
                onClick={() => table.setPageIndex(i)}
                className={cn(
                  'min-w-[2rem] rounded-lg px-2 py-1 text-xs font-medium transition-colors',
                  pageIndex === i ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted',
                )}
              >
                {i + 1}
              </button>
            ))}
            <button
              type="button"
              onClick={() => table.nextPage()}
              disabled={!table.getCanNextPage()}
              className="rounded-lg p-1.5 transition-colors hover:bg-muted disabled:cursor-not-allowed disabled:opacity-30"
              aria-label="Página siguiente"
            >
              <ChevronRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Eliminar tipo de caso</DialogTitle>
            <DialogDescription>
              ¿Seguro que desea eliminar «{deleteTarget?.nombre}»? Esta acción no se puede deshacer.
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
