import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Cloud, RefreshCw } from 'lucide-react';
import { api, type ClienteResponse } from '@/api/client';
import { ClienteHoldedBadge } from '@/components/clientes/ClienteHoldedBadge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface ClienteHoldedPanelProps {
  cliente: ClienteResponse;
  clienteId: string;
}

export function ClienteHoldedPanel({ cliente, clienteId }: ClienteHoldedPanelProps) {
  const queryClient = useQueryClient();

  const syncMutation = useMutation({
    mutationFn: () => api.sincronizarClienteHolded(clienteId, true),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['cliente', clienteId] });
      void queryClient.invalidateQueries({ queryKey: ['clientes'] });
    },
  });

  return (
    <div className="panel">
      <div className="panel-header">
        <div className="panel-header-icon">
          <Cloud className="h-5 w-5" />
        </div>
        <div className="flex-1">
          <h2 className="panel-title">Sincronización Holded</h2>
          <p className="text-sm text-muted-foreground">
            Mientras no cierre la contratación con pago, el registro es una{' '}
            <strong>oportunidad</strong>. Al completar la contratación se crea el contacto en Holded
            automáticamente.
          </p>
        </div>
        <ClienteHoldedBadge estado={cliente.holdedEstado} label={cliente.holdedEstadoLabel} />
      </div>

      <div className="space-y-4 p-6">
        {cliente.holdedEstado === 'oportunidad' && (
          <div className="rounded-lg border border-border bg-muted/30 p-4 text-sm text-muted-foreground">
            Este contacto aún no está en Holded. Se sincronizará cuando el cliente complete el pago
            y el abogado valide el cierre de la contratación.
          </div>
        )}

        {cliente.holdedEstado === 'sincronizado' && (
          <dl className="grid gap-2 text-sm sm:grid-cols-2">
            <div>
              <dt className="text-muted-foreground">ID contacto Holded</dt>
              <dd className="font-mono">{cliente.holdedContactId}</dd>
            </div>
            {cliente.holdedSyncedAt && (
              <div>
                <dt className="text-muted-foreground">Sincronizado el</dt>
                <dd>{new Date(cliente.holdedSyncedAt).toLocaleString('es-ES')}</dd>
              </div>
            )}
          </dl>
        )}

        {cliente.holdedEstado === 'error' && (
          <div className="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
            <p className="font-medium">Error en la última sincronización</p>
            <p className="mt-1">{cliente.holdedSyncError}</p>
          </div>
        )}

        {cliente.holdedEstado === 'error' && (
          <Button
            variant="outline"
            onClick={() => syncMutation.mutate()}
            disabled={syncMutation.isPending}
          >
            <RefreshCw className={cn('mr-2 h-4 w-4', syncMutation.isPending && 'animate-spin')} />
            Reintentar sincronización con Holded
          </Button>
        )}

        {cliente.holdedEstado === 'sincronizado' && (
          <Button
            variant="outline"
            onClick={() => syncMutation.mutate()}
            disabled={syncMutation.isPending}
          >
            <RefreshCw className={cn('mr-2 h-4 w-4', syncMutation.isPending && 'animate-spin')} />
            Volver a sincronizar
          </Button>
        )}

        {syncMutation.isError && (
          <p className="text-sm text-destructive">
            {(syncMutation.error as Error).message}
          </p>
        )}
        {syncMutation.data && !syncMutation.data.success && (
          <p className="text-sm text-destructive">{syncMutation.data.error}</p>
        )}
      </div>
    </div>
  );
}
