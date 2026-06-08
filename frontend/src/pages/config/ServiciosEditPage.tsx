import { useQuery } from '@tanstack/react-query';
import { Pencil } from 'lucide-react';
import { api } from '@/api/client';
import { ConfigBreadcrumb } from '@/components/config/ConfigBreadcrumb';
import { ServicioForm } from '@/components/config/ServicioForm';
import type { TipoServicioValue } from '@/lib/servicio-tipos';

interface ServiciosEditPageProps {
  servicioId: string;
}

export function ServiciosEditPage({ servicioId }: ServiciosEditPageProps) {
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['servicio', servicioId],
    queryFn: () => api.getServicio(servicioId),
    enabled: Boolean(servicioId),
  });

  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <ConfigBreadcrumb section="servicios" variant="edit" />

      <main className="flex-1 p-6 md:p-8">
        <div className="mx-auto flex max-w-[720px] flex-col gap-6">
          <div className="flex gap-3">
            <div className="panel-header-icon h-12 w-12 rounded-xl">
              <Pencil className="h-6 w-6" />
            </div>
            <div>
              <h1 className="page-title">Editar servicio</h1>
              <p className="page-subtitle">Modifique el área jurídica o el nombre del servicio.</p>
            </div>
          </div>

          {isLoading && (
            <div className="panel p-8 text-center text-sm text-muted-foreground">Cargando…</div>
          )}

          {isError && (
            <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">
              {error instanceof Error ? error.message : 'No se pudo cargar el servicio.'}
            </div>
          )}

          {data && (
            <ServicioForm
              key={data.id}
              mode="edit"
              servicioId={data.id}
              initialNombre={data.nombre}
              initialTipo={data.tipo as TipoServicioValue}
            />
          )}
        </div>
      </main>
    </div>
  );
}
