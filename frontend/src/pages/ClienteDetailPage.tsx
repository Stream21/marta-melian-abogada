import { Link } from '@tanstack/react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ArrowLeft, Lock } from 'lucide-react';
import { api } from '@/api/client';
import { ClienteDatosForm } from '@/components/clientes/ClienteDatosForm';
import { ClienteDocumentoIdentidadPanel } from '@/components/clientes/ClienteDocumentoIdentidadPanel';
import { ClienteExpedientesPanel } from '@/components/clientes/ClienteExpedientesPanel';
import { ClienteHoldedBadge } from '@/components/clientes/ClienteHoldedBadge';
import { ClienteHoldedPanel } from '@/components/clientes/ClienteHoldedPanel';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';

interface ClienteDetailPageProps {
  clienteId: string;
}

export function ClienteDetailPage({ clienteId }: ClienteDetailPageProps) {
  const queryClient = useQueryClient();

  const { data, isLoading, error } = useQuery({
    queryKey: ['cliente', clienteId],
    queryFn: () => api.getCliente(clienteId),
  });

  const saveMutation = useMutation({
    mutationFn: (body: Parameters<typeof api.putCliente>[1]) => api.putCliente(clienteId, body),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['cliente', clienteId] });
      void queryClient.invalidateQueries({ queryKey: ['clientes'] });
    },
  });

  if (isLoading) {
    return <p className="p-6 text-muted-foreground">Cargando ficha del cliente…</p>;
  }

  if (error || !data) {
    return (
      <div className="p-6">
        <p className="text-destructive">No se pudo cargar la ficha del cliente.</p>
        <Button variant="outline" className="mt-4" asChild>
          <Link to="/clientes">Volver al listado</Link>
        </Button>
      </div>
    );
  }

  const { cliente, expedientes, edicionBloqueada, motivoEdicionBloqueada } = data;

  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <main className="flex-1 p-6 md:p-8">
        <div className="mx-auto flex max-w-[1200px] flex-col gap-6">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <Button variant="ghost" size="sm" className="mb-2 -ml-2" asChild>
                <Link to="/clientes">
                  <ArrowLeft className="mr-2 h-4 w-4" />
                  Clientes
                </Link>
              </Button>
              <p className="section-label">Ficha de cliente</p>
              <h1 className="page-title">{cliente.nombre || 'Cliente sin nombre'}</h1>
              <div className="mt-2 flex flex-wrap items-center gap-2">
                <p className="page-subtitle">
                  {cliente.tipoDocumento} {cliente.numDocumento}
                  {cliente.telefono ? ` · ${cliente.telefono}` : ''}
                </p>
                <ClienteHoldedBadge estado={cliente.holdedEstado} label={cliente.holdedEstadoLabel} />
              </div>
            </div>
          </div>

          {edicionBloqueada && (
            <div className="flex gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
              <Lock className="mt-0.5 h-4 w-4 shrink-0" />
              <p>
                {motivoEdicionBloqueada ??
                  'Los datos están bloqueados mientras haya un expediente en fase de contratación.'}
              </p>
            </div>
          )}

          <Tabs defaultValue="datos" className="w-full">
            <TabsList className="mb-4">
              <TabsTrigger value="datos">Datos</TabsTrigger>
              <TabsTrigger value="documento">Documento identidad</TabsTrigger>
              <TabsTrigger value="expedientes">
                Expedientes ({expedientes.length})
              </TabsTrigger>
              <TabsTrigger value="holded">Holded</TabsTrigger>
            </TabsList>

            <TabsContent value="datos">
              <ClienteDatosForm
                cliente={cliente}
                readOnly={edicionBloqueada}
                onSubmit={(body) => saveMutation.mutate(body)}
                isSaving={saveMutation.isPending}
              />
              {saveMutation.isError && (
                <p className="mt-2 text-sm text-destructive">{(saveMutation.error as Error).message}</p>
              )}
            </TabsContent>

            <TabsContent value="documento">
              <ClienteDocumentoIdentidadPanel cliente={cliente} />
            </TabsContent>

            <TabsContent value="expedientes">
              <ClienteExpedientesPanel expedientes={expedientes} />
            </TabsContent>

            <TabsContent value="holded">
              <ClienteHoldedPanel cliente={cliente} clienteId={clienteId} />
            </TabsContent>
          </Tabs>
        </div>
      </main>
    </div>
  );
}
