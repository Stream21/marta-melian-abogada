import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useNavigate } from '@tanstack/react-router';
import { Plus } from 'lucide-react';
import { api } from '@/api/client';
import { ConfigExpedienteBreadcrumb } from '@/components/config/ConfigExpedienteBreadcrumb';
import { TiposCasoTable } from '@/components/config/TiposCasoTable';
import { Button } from '@/components/ui/button';

export function TiposCasoPage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [deleteError, setDeleteError] = useState<string | null>(null);

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['tipos-caso'],
    queryFn: () => api.getTiposCaso(),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string) => api.deleteTipoCaso(id),
    onMutate: () => setDeleteError(null),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['tipos-caso'] });
    },
    onError: (e) => {
      setDeleteError(e instanceof Error ? e.message : 'No se pudo eliminar el tipo de caso.');
    },
  });

  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <ConfigExpedienteBreadcrumb variant="list" />

      <main className="flex-1 p-6 md:p-8">
        <div className="mx-auto flex max-w-[1400px] flex-col gap-6">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div className="flex gap-3">
              <div>
                <h1 className="text-2xl font-bold text-primary">Gestión de Tipos de Caso</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                  Defina y organice los tipos de procedimientos legales para sus expedientes.
                </p>
              </div>
            </div>
            <Button asChild className="inline-flex shrink-0">
              <Link
                to={'/config/tipos-caso/nuevo' as never}
                title="Añadir un nuevo tipo de caso"
              >
                <Plus className="h-4 w-4" />
                Nuevo tipo
              </Link>
            </Button>
          </div>

          {isError && (
            <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">
              {error instanceof Error ? error.message : 'No se pudieron cargar los tipos de caso.'}
            </div>
          )}

          {deleteError && (
            <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">
              {deleteError}
            </div>
          )}

          <div className="panel overflow-hidden">
            <TiposCasoTable
              data={data ?? []}
              isLoading={isLoading}
              onRefresh={() => void refetch()}
              isRefreshing={isFetching}
              onEdit={(id) =>
                navigate({
                  to: '/config/tipos-caso/$tipoCasoId',
                  params: { tipoCasoId: id },
                } as never)
              }
              onDelete={(id) => deleteMutation.mutate(id)}
              deletingId={deleteMutation.isPending ? deleteMutation.variables ?? null : null}
            />
          </div>
        </div>
      </main>
    </div>
  );
}
