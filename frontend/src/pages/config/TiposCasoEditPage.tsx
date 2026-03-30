import { useQuery } from '@tanstack/react-query';
import { Pencil } from 'lucide-react';
import { api } from '@/api/client';
import { ConfigExpedienteBreadcrumb } from '@/components/config/ConfigExpedienteBreadcrumb';
import { TipoCasoForm } from '@/components/config/TipoCasoForm';

interface TiposCasoEditPageProps {
  tipoCasoId: string;
}

export function TiposCasoEditPage({ tipoCasoId }: TiposCasoEditPageProps) {
 
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['tipo-caso', tipoCasoId],
    queryFn: () => api.getTipoCaso(tipoCasoId),
    enabled: Boolean(tipoCasoId),
  });

  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <ConfigExpedienteBreadcrumb variant="edit" />

      <main className="flex-1 p-6 md:p-8">
        <div className="mx-auto flex max-w-[720px] flex-col gap-6">
          <div className="flex gap-3">
            <div className="panel-header-icon h-12 w-12 rounded-xl">
              <Pencil className="h-6 w-6" />
            </div>
            <div>
              <h1 className="page-title">Editar tipo de caso</h1>
              <p className="page-subtitle">
                Modifique el nombre o la descripción. El número de servicios asociados se calculará más adelante desde
                la relación entre tipos de caso y servicios.
              </p>
            </div>
          </div>

          {isLoading && (
            <div className="panel p-8 text-center text-sm text-muted-foreground">
              Cargando…
            </div>
          )}

          {isError && (
            <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">
              {error instanceof Error ? error.message : 'No se pudo cargar el tipo de caso.'}
            </div>
          )}

          {data && (
            <TipoCasoForm
              key={data.id}
              mode="edit"
              tipoCasoId={data.id}
              initialNombre={data.nombre}
              initialDescripcion={data.descripcion}
            />
          )}
        </div>
      </main>
    </div>
  );
}
