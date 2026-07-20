import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useNavigate } from '@tanstack/react-router';
import { Plus } from 'lucide-react';
import { api } from '@/api/client';
import { ConfigBreadcrumb } from '@/components/config/ConfigBreadcrumb';
import { ServiciosTable } from '@/components/config/ServiciosTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageShell } from '@/components/layout/PageShell';
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
    <>
      <ConfigBreadcrumb section="servicios" variant="list" />
      <PageShell>
        <PageHeader
          title="Servicios"
          subtitle="Defina los servicios jurídicos que ofrece el bufete."
          actions={
            <Button asChild className="inline-flex shrink-0">
              <Link to={'/config/servicios/nuevo' as never} title="Añadir un nuevo servicio">
                <Plus className="h-4 w-4" />
                Nuevo servicio
              </Link>
            </Button>
          }
        />

        {isError && (
          <div
            className="mb-4 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive"
            role="alert"
          >
            {error instanceof Error ? error.message : 'No se pudieron cargar los servicios.'}
          </div>
        )}

        {estadoError && (
          <div
            className="mb-4 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive"
            role="alert"
          >
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
      </PageShell>
    </>
  );
}
