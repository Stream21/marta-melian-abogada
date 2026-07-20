import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { FileStack, Plus } from 'lucide-react';
import { api, type FaseNegocio } from '@/api/client';
import { Button } from '@/components/ui/button';
import {
  DocumentoRequeridoFormModal,
  type DocumentoRequeridoFormValues,
} from '@/components/config/tramite/DocumentoRequeridoFormModal';
import { EnlaceClienteModal } from '@/components/expedientes/contratacion/EnlaceClienteModal';
import { RequerimientosPdfConjuntoModal } from '@/components/expedientes/requerimientos/RequerimientosPdfConjuntoModal';

interface ExpedienteGestionToolbarActionsProps {
  expedienteId: string;
  faseNegocio: FaseNegocio;
}

export function ExpedienteGestionToolbarActions({
  expedienteId,
  faseNegocio,
}: ExpedienteGestionToolbarActionsProps) {
  if (faseNegocio === 'contratacion') {
    return <ContratacionToolbarActions expedienteId={expedienteId} />;
  }
  if (faseNegocio === 'requerimientos') {
    return <RequerimientosToolbarActions expedienteId={expedienteId} />;
  }
  return null;
}

function ContratacionToolbarActions({ expedienteId }: { expedienteId: string }) {
  const { data } = useQuery({
    queryKey: ['contratacion', expedienteId],
    queryFn: () => api.getContratacion(expedienteId),
    refetchInterval: 8000,
    staleTime: 0,
  });

  if (!data || data.faseNegocio !== 'contratacion') {
    return null;
  }

  return (
    <div className="flex flex-wrap items-center gap-2">
      <EnlaceClienteModal expedienteId={expedienteId} accessUrl={data.accessUrl} />
    </div>
  );
}

function RequerimientosToolbarActions({ expedienteId }: { expedienteId: string }) {
  const queryClient = useQueryClient();
  const [mostrarAddDoc, setMostrarAddDoc] = useState(false);
  const [mostrarPdfConjunto, setMostrarPdfConjunto] = useState(false);

  const { data } = useQuery({
    queryKey: ['requerimientos', expedienteId],
    queryFn: () => api.getRequerimientos(expedienteId),
    refetchInterval: 8000,
  });

  const agregarMutation = useMutation({
    mutationFn: (values: DocumentoRequeridoFormValues) =>
      api.agregarDocumentoRequerimientos(expedienteId, values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['requerimientos', expedienteId] });
      setMostrarAddDoc(false);
    },
  });

  const avanzarMutation = useMutation({
    mutationFn: () => api.avanzarTramitacion(expedienteId),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['requerimientos', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expediente', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
    },
  });

  if (!data) {
    return null;
  }

  const docsValidados = data.documentos.filter(
    (doc) => doc.estado === 'validado' && doc.tieneArchivo,
  );

  return (
    <>
      <div className="flex w-full min-w-[280px] flex-col gap-2 sm:min-w-[420px]">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <div className="flex flex-wrap items-center gap-2">
            <EnlaceClienteModal expedienteId={expedienteId} accessUrl={data.accessUrl} />
            <Button
              variant="outline"
              size="sm"
              disabled={docsValidados.length === 0}
              onClick={() => setMostrarPdfConjunto(true)}
            >
              <FileStack className="mr-2 h-4 w-4" />
              PDF conjunto
            </Button>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Button variant="outline" size="sm" onClick={() => setMostrarAddDoc(true)}>
              <Plus className="mr-2 h-4 w-4" />
              Añadir documento
            </Button>
            <Button
              size="sm"
              disabled={!data.puedeAvanzarFase3 || avanzarMutation.isPending}
              onClick={() => avanzarMutation.mutate()}
            >
              {avanzarMutation.isPending ? 'Avanzando…' : 'Pasar a Fase 3'}
            </Button>
          </div>
        </div>
        {avanzarMutation.error && (
          <p className="text-sm text-destructive">{avanzarMutation.error.message}</p>
        )}
      </div>

      <DocumentoRequeridoFormModal
        open={mostrarAddDoc}
        mode="create"
        isPending={agregarMutation.isPending}
        onOpenChange={setMostrarAddDoc}
        onSubmit={(values) => agregarMutation.mutate(values)}
      />

      <RequerimientosPdfConjuntoModal
        open={mostrarPdfConjunto}
        onOpenChange={setMostrarPdfConjunto}
        expedienteId={expedienteId}
        documentos={data.documentos}
      />
    </>
  );
}
