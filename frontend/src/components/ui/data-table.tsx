import { useState } from 'react';
import {
  type ColumnDef,
  type ColumnFiltersState,
  type SortingState,
  type VisibilityState,
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from '@tanstack/react-table';
import { ChevronUp, ChevronDown, ChevronsUpDown, ChevronLeft, ChevronRight, Search, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

export interface FilterableColumn {
  id: string;
  title: string;
  options: { label: string; value: string }[];
}

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  filterableColumns?: FilterableColumn[];
  searchPlaceholder?: string;
  pageSize?: number;
}

function SortIcon({ sorted }: { sorted: false | 'asc' | 'desc' }) {
  if (sorted === 'asc') return <ChevronUp className="ml-1.5 h-3.5 w-3.5 shrink-0" />;
  if (sorted === 'desc') return <ChevronDown className="ml-1.5 h-3.5 w-3.5 shrink-0" />;
  return <ChevronsUpDown className="ml-1.5 h-3.5 w-3.5 shrink-0 opacity-30" />;
}

export function DataTable<TData, TValue>({
  columns,
  data,
  filterableColumns = [],
  searchPlaceholder = 'Buscar...',
  pageSize = 10,
}: DataTableProps<TData, TValue>) {
  const [sorting, setSorting] = useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [globalFilter, setGlobalFilter] = useState('');
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({});

  const table = useReactTable({
    data,
    columns,
    state: { sorting, columnFilters, globalFilter, columnVisibility },
    initialState: { pagination: { pageSize } },
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onGlobalFilterChange: setGlobalFilter,
    onColumnVisibilityChange: setColumnVisibility,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
  });

  const activeFilters = columnFilters.length;

  return (
    <div className="flex flex-col gap-0 h-full">
      {/* Toolbar */}
      <div className="flex flex-wrap items-center gap-3 p-5 border-b border-gray-100">
        <div className="relative flex-1 min-w-[180px] max-w-xs">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
          <input
            value={globalFilter}
            onChange={(e) => setGlobalFilter(e.target.value)}
            placeholder={searchPlaceholder}
            className="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#1e3a8a] focus:border-[#1e3a8a] transition-all"
          />
        </div>

        {filterableColumns.map((col) => {
          const currentValue = (table.getColumn(col.id)?.getFilterValue() as string) ?? '';
          return (
            <div key={col.id} className="relative">
              <select
                value={currentValue}
                onChange={(e) =>
                  table.getColumn(col.id)?.setFilterValue(e.target.value || undefined)
                }
                className={cn(
                  'appearance-none border rounded-lg py-2 pl-3 pr-8 text-sm focus:outline-none focus:ring-1 focus:ring-[#1e3a8a] transition-all cursor-pointer',
                  currentValue
                    ? 'border-[#1e3a8a] bg-blue-50 text-[#1e3a8a] font-medium'
                    : 'border-gray-200 bg-gray-50 text-gray-600',
                )}
              >
                <option value="">{col.title}</option>
                {col.options.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
              </select>
              <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400" />
            </div>
          );
        })}

        {activeFilters > 0 && (
          <button
            onClick={() => { setColumnFilters([]); setGlobalFilter(''); }}
            className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors px-2 py-1.5 rounded-lg hover:bg-gray-100"
          >
            <X className="h-3.5 w-3.5" />
            Limpiar filtros
            <span className="ml-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-[#1e3a8a] text-[10px] font-bold text-white">
              {activeFilters}
            </span>
          </button>
        )}
      </div>

      {/* Table */}
      <div className="flex-1 overflow-auto">
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((hg) => (
              <TableRow key={hg.id} className="bg-gray-50/50 hover:bg-gray-50/50 border-b border-gray-100">
                {hg.headers.map((header) => (
                  <TableHead
                    key={header.id}
                    className="px-6 py-3.5 text-[11px] font-bold uppercase tracking-wider text-gray-500"
                  >
                    {header.isPlaceholder ? null : header.column.getCanSort() ? (
                      <button
                        onClick={header.column.getToggleSortingHandler()}
                        className="flex items-center hover:text-gray-800 transition-colors"
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
                <TableRow
                  key={row.id}
                  className="hover:bg-blue-50/30 transition-colors border-b border-gray-50 group"
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id} className="px-6 py-4">
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell colSpan={columns.length} className="h-32 text-center text-gray-400 text-sm">
                  No se encontraron resultados.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {/* Pagination */}
      <div className="flex items-center justify-between px-6 py-3.5 border-t border-gray-100 bg-gray-50/50 text-sm text-gray-500">
        <span>
          {table.getFilteredRowModel().rows.length} resultado
          {table.getFilteredRowModel().rows.length !== 1 ? 's' : ''}
        </span>
        <div className="flex items-center gap-1">
          <button
            onClick={() => table.previousPage()}
            disabled={!table.getCanPreviousPage()}
            className="p-1.5 rounded-lg hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
          >
            <ChevronLeft className="h-4 w-4" />
          </button>
          <span className="px-3 text-xs font-medium">
            {table.getState().pagination.pageIndex + 1} / {table.getPageCount() || 1}
          </span>
          <button
            onClick={() => table.nextPage()}
            disabled={!table.getCanNextPage()}
            className="p-1.5 rounded-lg hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
          >
            <ChevronRight className="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  );
}
