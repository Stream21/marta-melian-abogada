import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useNavigate } from '@tanstack/react-router';
import { Plus } from 'lucide-react';
import { api } from '@/api/client';
import { ConfigBreadcrumb } from '@/components/config/ConfigBreadcrumb';
import { ServiciosTable } from '@/components/config/ServiciosTable';
import { Button } from '@/components/ui/button';

export function ServiciosPage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [incluirInactivos, setIncluirInactivos] = useState(false);
  const [estadoError, setEstadoError] = useState<string | null>(null);

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['servicios', { incluirInactivos }],
    queryFn: () => api.getServicios({ incluirInactivos }),
  });

  const toggleMutation = useMutation({
    mutationFn: ({ id, activo }: { id: string; activo: boolean }) => api.patchServicioEstado(id, activo),
    onMutate: () => setEstadoError(null),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['servicios'] });
    },
    onError: (e) => {
      setEstadoError(e instanceof Error ? e.message : 'No se pudo cambiar el estado del servicio.');
    },
  });

  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <ConfigBreadcrumb section="servicios" variant="list" />

      <main className="flex-1 p-6 md:p-8">
        <div className="mx-auto flex max-w-[1400px] flex-col gap-6">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <h1 className="text-2xl font-bold text-primary">Gestión de Servicios</h1>
              <p className="mt-1 text-sm text-muted-foreground">
                Defina los servicios jurídicos que ofrece el bufete.
              </p>
            </div>
            <Button asChild className="inline-flex shrink-0">
              <Link to={'/config/servicios/nuevo' as never} title="Añadir un nuevo servicio">
                <Plus className="h-4 w-4" />
                Nuevo servicio
              </Link>
            </Button>
          </div>

          {isError && (
            <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">
              {error instanceof Error ? error.message : 'No se pudieron cargar los servicios.'}
            </div>
          )}

          {estadoError && (
            <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">
              {estadoError}
            </div>
          )}

          <div className="panel overflow-hidden">
            <ServiciosTable
              data={data ?? []}
              isLoading={isLoading}
              onRefresh={() => void refetch()}
              isRefreshing={isFetching}
              incluirInactivos={incluirInactivos}
              onIncluirInactivosChange={setIncluirInactivos}
              onEdit={(id) =>
                navigate({
                  to: '/config/servicios/$servicioId',
                  params: { servicioId: id },
                } as never)
              }
              onToggleEstado={(id, activo) => toggleMutation.mutate({ id, activo })}
              togglingId={toggleMutation.isPending ? toggleMutation.variables?.id ?? null : null}
            />
          </div>
        </div>
      </main>
    </div>
  );
}
