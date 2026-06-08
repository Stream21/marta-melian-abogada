import { useMemo, useState } from 'react';

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

import {

  ChevronDown,

  ChevronLeft,

  ChevronRight,

  ChevronUp,

  ChevronsUpDown,

  Pencil,

  Power,

  RefreshCw,

} from 'lucide-react';

import type { ServicioResponse } from '@/api/client';

import { ConfigListToolbar } from '@/components/config/ConfigListToolbar';

import { Badge } from '@/components/ui/badge';

import { getTipoServicioOption, TIPOS_SERVICIO } from '@/lib/servicio-tipos';

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



export interface ServiciosTableProps {

  data: ServicioResponse[];

  isLoading?: boolean;

  onRefresh?: () => void;

  isRefreshing?: boolean;

  onEdit: (id: string) => void;

  onToggleEstado: (id: string, activo: boolean) => void;

  togglingId?: string | null;

  incluirInactivos: boolean;

  onIncluirInactivosChange: (value: boolean) => void;

}



export function ServiciosTable({

  data,

  isLoading,

  onRefresh,

  isRefreshing = false,

  onEdit,

  onToggleEstado,

  togglingId,

  incluirInactivos,

  onIncluirInactivosChange,

}: ServiciosTableProps) {

  const [sorting, setSorting] = useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [globalFilter, setGlobalFilter] = useState('');
  const [toggleTarget, setToggleTarget] = useState<ServicioResponse | null>(null);



  const columns = useMemo<ColumnDef<ServicioResponse>[]>(

    () => [

      {

        id: 'tipo',

        accessorKey: 'tipo',

        header: 'Área',

        filterFn: (row, _id, value) => !value || row.original.tipo === value,

        cell: ({ row }) => {

          const option = getTipoServicioOption(row.original.tipo);

          if (!option) {

            return <span className="text-sm text-muted-foreground">{row.original.tipoLabel}</span>;

          }

          const Icon = option.icon;

          return (

            <div className="flex items-center gap-3">

              <span

                className={cn(

                  'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',

                  option.iconClass,

                )}

                title={option.label}

              >

                <Icon className="h-4 w-4" aria-hidden />

              </span>

              <span className="text-sm text-foreground">{option.shortLabel}</span>

            </div>

          );

        },

      },

      {

        id: 'nombre',

        accessorKey: 'nombre',

        header: 'Nombre',

        cell: ({ row }) => (

          <span className="font-semibold text-foreground">{row.original.nombre}</span>

        ),

      },

      {

        id: 'estado',

        accessorKey: 'activo',

        header: 'Estado',

        enableColumnFilter: false,

        cell: ({ row }) => (

          <Badge variant={row.original.activo ? 'success' : 'secondary'}>

            {row.original.activo ? 'Activo' : 'Inactivo'}

          </Badge>

        ),

      },

      {

        id: 'acciones',

        header: 'Acciones',

        enableSorting: false,

        enableColumnFilter: false,

        cell: ({ row }) => {

          const busy = togglingId === row.original.id;

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

                className={cn(

                  'rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted disabled:opacity-40',

                  row.original.activo ? 'hover:text-destructive' : 'hover:text-emerald-600',

                )}

                aria-label={row.original.activo ? 'Desactivar' : 'Activar'}

                onClick={() => setToggleTarget(row.original)}

                disabled={busy}

              >

                <Power className="h-4 w-4" />

              </button>

            </div>

          );

        },

      },

    ],

    [onEdit, togglingId],

  );



  const table = useReactTable({

    data,

    columns,

    state: { sorting, columnFilters, globalFilter },
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,

    onGlobalFilterChange: setGlobalFilter,

    globalFilterFn: (row, _columnId, filterValue) => {

      const q = String(filterValue ?? '').trim().toLowerCase();

      if (!q) return true;

      return row.original.nombre.toLowerCase().includes(q);

    },

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



  const areaFilter = (table.getColumn('tipo')?.getFilterValue() as string) ?? '';
  const hasActiveFilters = Boolean(globalFilter || areaFilter);

  const clearFilters = () => {
    setGlobalFilter('');
    setColumnFilters([]);
  };



  const confirmToggle = () => {

    if (toggleTarget) {

      onToggleEstado(toggleTarget.id, !toggleTarget.activo);

      setToggleTarget(null);

    }

  };



  if (isLoading) {

    return (

      <div className="flex items-center justify-center py-16 text-sm text-muted-foreground">

        Cargando servicios…

      </div>

    );

  }



  return (

    <>

      <div className="flex flex-col overflow-hidden">

        <ConfigListToolbar

          search={globalFilter}

          onSearchChange={setGlobalFilter}

          searchPlaceholder="Buscar por nombre…"

          incluirInactivos={incluirInactivos}

          onIncluirInactivosChange={onIncluirInactivosChange}

          selectFilters={[

            {

              id: 'area',

              label: 'Área jurídica',

              emptyLabel: 'Todas las áreas',
              value: areaFilter,
              onChange: (value) => table.getColumn('tipo')?.setFilterValue(value || undefined),

              options: TIPOS_SERVICIO.map((t) => ({ value: t.value, label: t.shortLabel })),

            },

          ]}

          onClear={clearFilters}

        />



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

            </TableHeader>

            <TableBody>

              {table.getRowModel().rows.length ? (

                table.getRowModel().rows.map((row) => (

                  <TableRow key={row.id} className="group border-b transition-colors hover:bg-primary/5">

                    {row.getVisibleCells().map((cell) => (

                      <TableCell

                        key={cell.id}

                        className={cn('px-6 py-4', cell.column.id === 'acciones' && 'text-right')}

                      >

                        {flexRender(cell.column.columnDef.cell, cell.getContext())}

                      </TableCell>

                    ))}

                  </TableRow>

                ))

              ) : (

                <TableRow>

                  <TableCell colSpan={columns.length} className="h-32 text-center text-sm text-muted-foreground">

                    {data.length === 0

                      ? 'No hay servicios registrados.'

                      : hasActiveFilters

                        ? 'No se encontraron resultados con los filtros aplicados.'

                        : 'No se encontraron resultados.'}

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



      <Dialog open={!!toggleTarget} onOpenChange={(open) => !open && setToggleTarget(null)}>

        <DialogContent>

          <DialogHeader>

            <DialogTitle>{toggleTarget?.activo ? 'Desactivar servicio' : 'Activar servicio'}</DialogTitle>

            <DialogDescription>

              {toggleTarget?.activo

                ? `¿Desactivar «${toggleTarget?.nombre}»? Podrá reactivarse más adelante.`

                : `¿Activar «${toggleTarget?.nombre}»?`}

            </DialogDescription>

          </DialogHeader>

          <DialogFooter>

            <Button type="button" variant="outline" onClick={() => setToggleTarget(null)}>

              Cancelar

            </Button>

            <Button

              type="button"

              variant={toggleTarget?.activo ? 'destructive' : 'default'}

              onClick={confirmToggle}

            >

              {toggleTarget?.activo ? 'Desactivar' : 'Activar'}

            </Button>

          </DialogFooter>

        </DialogContent>

      </Dialog>

    </>

  );

}

