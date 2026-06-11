import { Link } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import { Plus, Users } from 'lucide-react';
import { api } from '@/api/client';
import { ClientesTable } from '@/components/clientes/ClientesTable';
import { Button } from '@/components/ui/button';

export function ClientesPage() {
  const { data = [], isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['clientes'],
    queryFn: () => api.getClientes(),
  });

  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <main className="flex-1 p-6 md:p-8">
        <div className="mx-auto flex max-w-[1400px] flex-col gap-6">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div className="flex items-start gap-3">
              <Users className="mt-1 h-7 w-7 text-primary" />
              <div>
                <h1 className="text-2xl font-bold text-primary">Clientes</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                  Oportunidades y clientes del bufete. La sincronización con Holded se activa al
                  cerrar la contratación con pago.
                </p>
              </div>
            </div>
            <Button asChild className="shrink-0">
              <Link to="/clientes/nuevo">
                <Plus className="mr-2 h-4 w-4" />
                Nuevo cliente
              </Link>
            </Button>
          </div>

          {isError && (
            <p className="text-sm text-destructive">
              {(error as Error).message}
            </p>
          )}

          <ClientesTable
            data={data}
            isLoading={isLoading}
            isFetching={isFetching}
            onRefresh={() => void refetch()}
          />
        </div>
      </main>
    </div>
  );
}
