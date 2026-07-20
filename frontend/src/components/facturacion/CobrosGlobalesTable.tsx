import { useMemo, type KeyboardEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from '@tanstack/react-router';
import { AlertTriangle, Download, ExternalLink, Loader2, RefreshCw } from 'lucide-react';
import { api, type CobroGlobalItem } from '@/api/client';
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

const fmt = (n: string) =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(parseFloat(n));

const statusVariant: Record<CobroGlobalItem['status'], 'success' | 'warning' | 'destructive'> = {
  paid: 'success',
  pending: 'warning',
  failed: 'destructive',
};

const holdedVariant: Record<
  CobroGlobalItem['holdedEstado'],
  'default' | 'secondary' | 'success' | 'warning' | 'destructive'
> = {
  sincronizado: 'success',
  pendiente_sync: 'warning',
  error: 'destructive',
  no_aplica: 'secondary',
};

interface CobrosGlobalesTableProps {
  items: CobroGlobalItem[];
  search: string;
  onSearchChange: (value: string) => void;
  onSearchKeyDown?: (event: KeyboardEvent<HTMLInputElement>) => void;
  searchPlaceholder?: string;
  estadoCobro: string[];
  onEstadoCobroChange: (value: string[]) => void;
  holdedEstado: string[];
  onHoldedEstadoChange: (value: string[]) => void;
  tipo: string[];
  onTipoChange: (value: string[]) => void;
}

export function CobrosGlobalesTable({
  items,
  search,
  onSearchChange,
  onSearchKeyDown,
  searchPlaceholder,
  estadoCobro,
  onEstadoCobroChange,
  holdedEstado,
  onHoldedEstadoChange,
  tipo,
  onTipoChange,
}: CobrosGlobalesTableProps) {
  const queryClient = useQueryClient();

  const syncMutation = useMutation({
    mutationFn: (paymentId: string) => api.sincronizarPagoHolded(paymentId),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['cobros-globales'] }),
  });

  const selectFilters = useMemo(
    () => [
      {
        id: 'estadoCobro',
        label: 'Cobro',
        emptyLabel: 'Todos los cobros',
        values: estadoCobro,
        onChange: onEstadoCobroChange,
        options: [
          { value: 'paid', label: 'Cobrado' },
          { value: 'pending', label: 'Pendiente' },
          { value: 'failed', label: 'Fallido' },
        ],
      },
      {
        id: 'holdedEstado',
        label: 'Holded',
        emptyLabel: 'Todo Holded',
        values: holdedEstado,
        onChange: onHoldedEstadoChange,
        options: [
          { value: 'sincronizado', label: 'Sincronizado' },
          { value: 'pendiente_sync', label: 'Pendiente sync' },
          { value: 'error', label: 'Error' },
          { value: 'no_aplica', label: 'No aplica' },
        ],
      },
      {
        id: 'tipo',
        label: 'Canal',
        emptyLabel: 'Todos los canales',
        values: tipo,
        onChange: onTipoChange,
        options: [
          { value: 'link', label: 'Stripe' },
          { value: 'manual', label: 'Manual' },
          { value: 'installment', label: 'Cuotas' },
        ],
      },
    ],
    [estadoCobro, holdedEstado, tipo, onEstadoCobroChange, onHoldedEstadoChange, onTipoChange],
  );

  return (
    <div className="panel overflow-hidden">
      <ConfigListToolbar
        search={search}
        onSearchChange={onSearchChange}
        onSearchKeyDown={onSearchKeyDown}
        searchPlaceholder={searchPlaceholder ?? 'Buscar expediente o cliente… (Enter)'}
        selectFilters={selectFilters}
      />

      {items.length === 0 ? (
        <div className="flex items-center justify-center py-16 text-sm text-muted-foreground">
          No hay cobros que coincidan con los filtros.
        </div>
      ) : (
        <Table>
          <TableHeader>
            <TableRow className="bg-muted/50">
              <TableHead className="section-label">Fecha</TableHead>
              <TableHead className="section-label">Expediente</TableHead>
              <TableHead className="section-label">Cliente</TableHead>
              <TableHead className="text-right section-label">Importe</TableHead>
              <TableHead className="section-label">Canal</TableHead>
              <TableHead className="section-label">Cobro</TableHead>
              <TableHead className="section-label">Holded</TableHead>
              <TableHead className="section-label">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {items.map((item) => {
              const canSync =
                item.status === 'paid' &&
                (item.holdedEstado === 'pendiente_sync' || item.holdedEstado === 'error');
              const syncing = syncMutation.isPending && syncMutation.variables === item.id;

              return (
                <TableRow key={item.id} className="text-sm">
                  <TableCell className="text-muted-foreground whitespace-nowrap">
                    {new Date(item.createdAt).toLocaleDateString('es-ES')}
                  </TableCell>
                  <TableCell>
                    <Link
                      to="/expedientes/$expedienteId"
                      params={{ expedienteId: item.expedienteId }}
                      className="font-mono text-primary hover:underline"
                    >
                      {item.expedienteNumero}
                    </Link>
                  </TableCell>
                  <TableCell className="text-foreground">{item.clienteNombre}</TableCell>
                  <TableCell className="text-right font-medium">{fmt(item.amount)}</TableCell>
                  <TableCell>
                    <Badge variant="outline">{item.typeLabel}</Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant={statusVariant[item.status]}>{item.statusLabel}</Badge>
                  </TableCell>
                  <TableCell>
                    <div className="space-y-1">
                      <Badge variant={holdedVariant[item.holdedEstado]}>{item.holdedEstadoLabel}</Badge>
                      {item.holdedSyncError && (
                        <p className="flex items-start gap-1 text-xs text-destructive max-w-[180px]">
                          <AlertTriangle className="h-3 w-3 shrink-0 mt-0.5" />
                          <span className="line-clamp-2">{item.holdedSyncError}</span>
                        </p>
                      )}
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-1">
                      {canSync && (
                        <Button
                          variant="ghost"
                          size="icon"
                          className="h-7 w-7"
                          title="Sincronizar con Holded"
                          disabled={syncing}
                          onClick={() => syncMutation.mutate(item.id)}
                        >
                          {syncing ? (
                            <Loader2 className="h-3.5 w-3.5 animate-spin" />
                          ) : (
                            <RefreshCw className="h-3.5 w-3.5" />
                          )}
                        </Button>
                      )}
                      {item.holdedEstado === 'sincronizado' && item.pdfUrl && (
                        <Button variant="ghost" size="icon" className="h-7 w-7" asChild>
                          <a href={item.pdfUrl} target="_blank" rel="noreferrer" title="Descargar PDF">
                            <Download className="h-3.5 w-3.5" />
                          </a>
                        </Button>
                      )}
                      {item.holdedInvoiceId && (
                        <Button variant="ghost" size="icon" className="h-7 w-7" asChild>
                          <a
                            href={`https://app.holded.com/invoices/${item.holdedInvoiceId}`}
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
              );
            })}
          </TableBody>
        </Table>
      )}
    </div>
  );
}
