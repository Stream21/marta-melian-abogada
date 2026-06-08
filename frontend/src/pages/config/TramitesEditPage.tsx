import { useQuery } from '@tanstack/react-query';
import { Pencil } from 'lucide-react';
import { api } from '@/api/client';
import { ConfigBreadcrumb } from '@/components/config/ConfigBreadcrumb';
import { TramiteForm } from '@/components/config/TramiteForm';

interface TramitesEditPageProps {
  tramiteId: string;
}

export function TramitesEditPage({ tramiteId }: TramitesEditPageProps) {
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['tramite', tramiteId],
    queryFn: () => api.getTramite(tramiteId),
    enabled: Boolean(tramiteId),
  });

  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <ConfigBreadcrumb section="tramites" variant="edit" />

      <main className="flex-1 p-6 md:p-8">
        <div className="mx-auto flex max-w-[720px] flex-col gap-6">
          <div className="flex gap-3">
            <div className="panel-header-icon h-12 w-12 rounded-xl">
              <Pencil className="h-6 w-6" />
            </div>
            <div>
              <h1 className="page-title">Editar trámite</h1>
              <p className="page-subtitle">Modifique los datos del trámite, incluida plataforma y procurador.</p>
            </div>
          </div>

          {isLoading && (
            <div className="panel p-8 text-center text-sm text-muted-foreground">Cargando…</div>
          )}

          {isError && (
            <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">
              {error instanceof Error ? error.message : 'No se pudo cargar el trámite.'}
            </div>
          )}

          {data && (
            <TramiteForm
              key={data.id}
              mode="edit"
              tramiteId={data.id}
              initialServicioId={data.servicioId}
              initialNombre={data.nombre}
              initialHonorarios={data.honorarios}
              initialPlataforma={data.plataforma as 'mercurio' | 'lexnet'}
              initialRequiereProcurador={data.requiereProcurador}
            />
          )}
        </div>
      </main>
    </div>
  );
}
