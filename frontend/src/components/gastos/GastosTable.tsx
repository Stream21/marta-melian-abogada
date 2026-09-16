import { type KeyboardEvent } from 'react';
import { Link } from '@tanstack/react-router';
import { FileText, Pencil } from 'lucide-react';
import { openAuthenticatedDocument, type GastoItem } from '@/api/client';
import { ConfigListToolbar } from '@/components/config/ConfigListToolbar';
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

const fmt = (n: string) =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(parseFloat(n));

const fmtFecha = (iso: string) => {
  const [y, m, d] = iso.split('-');
  if (!y || !m || !d) return iso;
  return `${d}/${m}/${y}`;
};

interface GastosTableProps {
  items: GastoItem[];
  search: string;
  onSearchChange: (value: string) => void;
  onSearchKeyDown?: (event: KeyboardEvent<HTMLInputElement>) => void;
  fechaDesde: string;
  onFechaDesdeChange: (value: string) => void;
  fechaHasta: string;
  onFechaHastaChange: (value: string) => void;
  categoria: string;
  onCategoriaChange: (value: string) => void;
}

export function GastosTable({
  items,
  search,
  onSearchChange,
  onSearchKeyDown,
  fechaDesde,
  onFechaDesdeChange,
  fechaHasta,
  onFechaHastaChange,
  categoria,
  onCategoriaChange,
}: GastosTableProps) {
  return (
    <div className="panel overflow-hidden">
      <ConfigListToolbar
        search={search}
        onSearchChange={onSearchChange}
        onSearchKeyDown={onSearchKeyDown}
        searchPlaceholder="Buscar concepto, categoría o notas… (Enter)"
        trailing={
          <div className="flex flex-wrap items-center gap-2">
            <Input
              type="text"
              value={categoria}
              onChange={(e) => onCategoriaChange(e.target.value)}
              className="h-9 w-[140px]"
              placeholder="Categoría"
              aria-label="Filtrar por categoría"
            />
            <Input
              type="date"
              value={fechaDesde}
              onChange={(e) => onFechaDesdeChange(e.target.value)}
              className="h-9 w-[150px]"
              aria-label="Fecha desde"
            />
            <span className="text-xs text-muted-foreground">a</span>
            <Input
              type="date"
              value={fechaHasta}
              onChange={(e) => onFechaHastaChange(e.target.value)}
              className="h-9 w-[150px]"
              aria-label="Fecha hasta"
            />
          </div>
        }
      />

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Fecha</TableHead>
            <TableHead>Concepto</TableHead>
            <TableHead>Categoría</TableHead>
            <TableHead className="text-right">Importe</TableHead>
            <TableHead>Factura</TableHead>
            <TableHead className="w-[80px]" />
          </TableRow>
        </TableHeader>
        <TableBody>
          {items.length === 0 ? (
            <TableRow>
              <TableCell colSpan={6} className="py-10 text-center text-sm text-muted-foreground">
                No hay gastos con estos filtros.
              </TableCell>
            </TableRow>
          ) : (
            items.map((gasto) => (
              <TableRow key={gasto.id}>
                <TableCell className="whitespace-nowrap text-sm">{fmtFecha(gasto.fecha)}</TableCell>
                <TableCell className="max-w-[280px] truncate font-medium">{gasto.concepto}</TableCell>
                <TableCell className="text-sm text-muted-foreground">
                  {gasto.categoria ?? '—'}
                </TableCell>
                <TableCell className="text-right font-medium tabular-nums">
                  {fmt(gasto.importe)}
                </TableCell>
                <TableCell>
                  {gasto.tieneFactura && gasto.facturaUrl ? (
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      className="h-8 gap-1.5 px-2"
                      onClick={() => void openAuthenticatedDocument(gasto.facturaUrl!)}
                    >
                      <FileText className="h-4 w-4" />
                      Ver
                    </Button>
                  ) : (
                    <span className="text-sm text-muted-foreground">—</span>
                  )}
                </TableCell>
                <TableCell>
                  <Button asChild variant="ghost" size="sm" className="h-8 w-8 p-0">
                    <Link to="/gastos/$gastoId" params={{ gastoId: gasto.id }}>
                      <Pencil className="h-4 w-4" />
                      <span className="sr-only">Editar</span>
                    </Link>
                  </Button>
                </TableCell>
              </TableRow>
            ))
          )}
        </TableBody>
      </Table>
    </div>
  );
}
