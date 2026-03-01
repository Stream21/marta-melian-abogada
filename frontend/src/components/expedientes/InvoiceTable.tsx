import { api, type InvoiceResponse } from '@/api/client';
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
import { cn } from '@/lib/utils';
import { Download, ExternalLink } from 'lucide-react';

const fmt = (n: string) =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(parseFloat(n));

const holdedStatusLabel: Record<InvoiceResponse['estadoHolded'], string> = {
  draft: 'Borrador',
  outstanding: 'Pendiente',
  paid: 'Pagada',
  overdue: 'Vencida',
  void: 'Anulada',
};

const holdedStatusVariant: Record<
  InvoiceResponse['estadoHolded'],
  'default' | 'secondary' | 'destructive' | 'outline'
> = {
  draft: 'secondary',
  outstanding: 'outline',
  paid: 'default',
  overdue: 'destructive',
  void: 'secondary',
};

interface InvoiceTableProps {
  invoices: InvoiceResponse[];
  expedienteId: string;
}

export function InvoiceTable({ invoices, expedienteId }: InvoiceTableProps) {
  if (invoices.length === 0) {
    return (
      <div className="flex items-center justify-center rounded-lg border border-dashed border-slate-200 py-10 text-sm text-slate-400">
        No hay facturas generadas aún.
      </div>
    );
  }

  return (
    <div className="overflow-hidden rounded-lg border border-slate-200">
      <Table>
        <TableHeader>
          <TableRow className="bg-slate-50">
            <TableHead className="text-xs font-semibold text-slate-500">Nº Factura</TableHead>
            <TableHead className="text-xs font-semibold text-slate-500">Modalidad</TableHead>
            <TableHead className="text-xs font-semibold text-slate-500">Fecha</TableHead>
            <TableHead className="text-right text-xs font-semibold text-slate-500">
              Importe
            </TableHead>
            <TableHead className="text-xs font-semibold text-slate-500">Estado Holded</TableHead>
            <TableHead className="text-xs font-semibold text-slate-500">Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {invoices.map((inv) => (
            <TableRow key={inv.id} className="text-sm">
              <TableCell className="font-mono text-slate-700">{inv.numero}</TableCell>
              <TableCell className="text-slate-600">{inv.modalidad}</TableCell>
              <TableCell className="text-slate-500">
                {new Date(inv.fecha).toLocaleDateString('es-ES')}
              </TableCell>
              <TableCell className="text-right font-medium text-slate-700">
                {fmt(inv.importe)}
              </TableCell>
              <TableCell>
                <Badge
                  variant={holdedStatusVariant[inv.estadoHolded]}
                  className={cn(
                    inv.estadoHolded === 'paid' &&
                      'bg-emerald-100 text-emerald-700 hover:bg-emerald-100',
                    inv.estadoHolded === 'outstanding' && 'border-amber-300 text-amber-700',
                    inv.estadoHolded === 'overdue' && 'bg-red-100 text-red-700 hover:bg-red-100',
                  )}
                >
                  {holdedStatusLabel[inv.estadoHolded]}
                </Badge>
              </TableCell>
              <TableCell>
                <div className="flex items-center gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-7 w-7 text-slate-400 hover:text-slate-700"
                    asChild
                  >
                    <a
                      href={api.getPdfUrl(expedienteId, inv.id)}
                      target="_blank"
                      rel="noreferrer"
                      title="Descargar PDF"
                    >
                      <Download className="h-3.5 w-3.5" />
                    </a>
                  </Button>
                  {inv.holdedId && (
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-7 w-7 text-slate-400 hover:text-slate-700"
                      asChild
                    >
                      <a
                        href={`https://app.holded.com/invoices/${inv.holdedId}`}
                        target="_blank"
                        rel="noreferrer"
                        title="Ver en Holded"
                      >
                        <ExternalLink className="h-3.5 w-3.5" />
                      </a>
                    </Button>
                  )}
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
