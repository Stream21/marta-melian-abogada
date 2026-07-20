import { Link } from '@tanstack/react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Plus } from 'lucide-react';
import { api } from '@/api/client';
import { ClientesTable } from '@/components/clientes/ClientesTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageShell } from '@/components/layout/PageShell';
import { Button } from '@/components/ui/button';

export function ClientesPage() {
  const queryClient = useQueryClient();
  const { data = [], isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['clientes'],
    queryFn: () => api.getClientes(),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string) => api.deleteCliente(id),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['clientes'] });
    },
  });

  return (
    <PageShell>
      <PageHeader
        title="Clientes"
        subtitle="Oportunidades y clientes del bufete. La sincronización con Holded se activa al cerrar la contratación con pago."
        actions={
          <Button asChild className="shrink-0">
            <Link to="/clientes/nuevo">
              <Plus className="mr-2 h-4 w-4" />
              Nuevo cliente
            </Link>
          </Button>
        }
      />

      {isError && (
        <p className="mb-4 text-sm text-destructive">{(error as Error).message}</p>
      )}

      {deleteMutation.isError && (
        <p className="mb-4 text-sm text-destructive">
          {(deleteMutation.error as Error).message}
        </p>
      )}

      <ClientesTable
        data={data}
        isLoading={isLoading}
        isFetching={isFetching}
        deletingId={deleteMutation.isPending ? deleteMutation.variables ?? null : null}
        onRefresh={() => void refetch()}
        onDelete={(id) => deleteMutation.mutate(id)}
      />
    </PageShell>
  );
}
