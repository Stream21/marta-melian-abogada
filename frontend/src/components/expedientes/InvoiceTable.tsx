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
  'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning'
> = {
  draft: 'secondary',
  outstanding: 'warning',
  paid: 'success',
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
      <div className="flex items-center justify-center rounded-lg border border-dashed py-10 text-sm text-muted-foreground">
        No hay facturas generadas aún.
      </div>
    );
  }

  return (
    <div className="panel">
      <Table>
        <TableHeader>
          <TableRow className="bg-muted/50">
            <TableHead className="section-label">Nº Factura</TableHead>
            <TableHead className="section-label">Modalidad</TableHead>
            <TableHead className="section-label">Fecha</TableHead>
            <TableHead className="text-right section-label">Importe</TableHead>
            <TableHead className="section-label">Estado Holded</TableHead>
            <TableHead className="section-label">Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {invoices.map((inv) => (
            <TableRow key={inv.id} className="text-sm">
              <TableCell className="font-mono text-foreground">{inv.numero}</TableCell>
              <TableCell className="text-muted-foreground">{inv.modalidad}</TableCell>
              <TableCell className="text-muted-foreground">
                {new Date(inv.fecha).toLocaleDateString('es-ES')}
              </TableCell>
              <TableCell className="text-right font-medium text-foreground">
                {fmt(inv.importe)}
              </TableCell>
              <TableCell>
                <Badge variant={holdedStatusVariant[inv.estadoHolded]}>
                  {holdedStatusLabel[inv.estadoHolded]}
                </Badge>
              </TableCell>
              <TableCell>
                <div className="flex items-center gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-7 w-7 text-muted-foreground hover:text-foreground"
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
                      className="h-7 w-7 text-muted-foreground hover:text-foreground"
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
