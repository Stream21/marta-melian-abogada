import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useNavigate } from '@tanstack/react-router';
import { Plus } from 'lucide-react';
import { api } from '@/api/client';
import { ConfigBreadcrumb } from '@/components/config/ConfigBreadcrumb';
import { TramitesTable } from '@/components/config/TramitesTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageShell } from '@/components/layout/PageShell';
import { Button } from '@/components/ui/button';

export function TramitesPage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [incluirInactivos, setIncluirInactivos] = useState(false);
  const [estadoError, setEstadoError] = useState<string | null>(null);

  const { data: servicios = [] } = useQuery({
    queryKey: ['servicios', { incluirInactivos: true }],
    queryFn: () => api.getServicios({ incluirInactivos: true }),
  });

  const servicioOptions = useMemo(
    () => servicios.map((s) => ({ value: s.id, label: s.nombre })),
    [servicios],
  );

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['tramites', { incluirInactivos }],
    queryFn: () => api.getTramites({ incluirInactivos }),
  });

  const toggleMutation = useMutation({
    mutationFn: ({ id, activo }: { id: string; activo: boolean }) => api.patchTramiteEstado(id, activo),
    onMutate: () => setEstadoError(null),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['tramites'] });
    },
    onError: (e) => {
      setEstadoError(e instanceof Error ? e.message : 'No se pudo cambiar el estado del trámite.');
    },
  });

  return (
    <>
      <ConfigBreadcrumb section="tramites" variant="list" />
      <PageShell>
        <PageHeader
          title="Trámites"
          subtitle="Defina los trámites asociados a cada servicio jurídico. Use el icono de configuración para definir la hoja de encargo y la documentación requerida."
          actions={
            <Button asChild className="inline-flex shrink-0">
              <Link to={'/config/tramites/nuevo' as never} title="Añadir un nuevo trámite">
                <Plus className="h-4 w-4" />
                Nuevo trámite
              </Link>
            </Button>
          }
        />

        {isError && (
          <div
            className="mb-4 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive"
            role="alert"
          >
            {error instanceof Error ? error.message : 'No se pudieron cargar los trámites.'}
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
          <TramitesTable
            data={data ?? []}
            isLoading={isLoading}
            onRefresh={() => void refetch()}
            isRefreshing={isFetching}
            incluirInactivos={incluirInactivos}
            onIncluirInactivosChange={setIncluirInactivos}
            servicioOptions={servicioOptions}
            onEdit={(id) =>
              navigate({
                to: '/config/tramites/$tramiteId',
                params: { tramiteId: id },
              } as never)
            }
            onConfigure={(id) =>
              navigate({
                to: '/config/tramites/$tramiteId/configuracion',
                params: { tramiteId: id },
                search: { tab: 'hoja-encargo' },
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
