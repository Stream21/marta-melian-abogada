import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Plus } from 'lucide-react';
import { api, type FaseNegocio } from '@/api/client';
import { Button } from '@/components/ui/button';
import {
  DocumentoRequeridoFormModal,
  type DocumentoRequeridoFormValues,
} from '@/components/config/tramite/DocumentoRequeridoFormModal';
import { EnlaceClienteModal } from '@/components/expedientes/contratacion/EnlaceClienteModal';
import { PasarFaseDuracionModal } from '@/components/expedientes/PasarFaseDuracionModal';

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
  if (faseNegocio === 'documentacion') {
    return <DocumentacionToolbarActions expedienteId={expedienteId} />;
  }
  if (faseNegocio === 'tramitacion') {
    return <TramitacionToolbarActions expedienteId={expedienteId} />;
  }
  if (faseNegocio === 'resolucion') {
    return <ResolucionToolbarActions expedienteId={expedienteId} />;
  }
  return null;
}

function ResolucionToolbarActions({ expedienteId }: { expedienteId: string }) {
  const { data } = useQuery({
    queryKey: ['resolucion', expedienteId],
    queryFn: () => api.getResolucion(expedienteId),
    refetchInterval: 10000,
  });

  if (!data?.accessUrl) {
    return null;
  }

  return (
    <div className="flex flex-wrap items-center gap-2">
      <EnlaceClienteModal expedienteId={expedienteId} accessUrl={data.accessUrl} />
    </div>
  );
}

function TramitacionToolbarActions({ expedienteId }: { expedienteId: string }) {
  const queryClient = useQueryClient();
  const [modalOpen, setModalOpen] = useState(false);
  const { data } = useQuery({
    queryKey: ['tramitacion', expedienteId],
    queryFn: () => api.getTramitacion(expedienteId),
    refetchInterval: 10000,
  });

  const avanzarMutation = useMutation({
    mutationFn: (fechaVencimientoFase: string) =>
      api.avanzarResolucion(expedienteId, { fechaVencimientoFase }),
    onSuccess: () => {
      setModalOpen(false);
      void queryClient.invalidateQueries({ queryKey: ['tramitacion', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expediente', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
    },
  });

  if (!data) {
    return null;
  }

  return (
    <div className="flex flex-wrap items-center gap-2">
      {data.accessUrl && (
        <EnlaceClienteModal expedienteId={expedienteId} accessUrl={data.accessUrl} />
      )}
      <Button
        size="sm"
        disabled={!data.puedeAvanzarResolucion || avanzarMutation.isPending}
        onClick={() => setModalOpen(true)}
      >
        Pasar a Fase 4
      </Button>
      <PasarFaseDuracionModal
        open={modalOpen}
        onOpenChange={setModalOpen}
        faseDestinoNumero={4}
        faseDestinoLabel="Resolución"
        confirmLabel="Pasar a Fase 4"
        pending={avanzarMutation.isPending}
        error={avanzarMutation.error?.message ?? null}
        onConfirm={(fechaVencimientoFase) => avanzarMutation.mutate(fechaVencimientoFase)}
      />
    </div>
  );
}

function ContratacionToolbarActions({ expedienteId }: { expedienteId: string }) {
  const queryClient = useQueryClient();
  const [modalOpen, setModalOpen] = useState(false);
  const { data } = useQuery({
    queryKey: ['contratacion', expedienteId],
    queryFn: () => api.getContratacion(expedienteId),
    refetchInterval: 8000,
    staleTime: 0,
  });

  const avanzarMutation = useMutation({
    mutationFn: (fechaVencimientoFase: string) =>
      api.avanzarDocumentacion(expedienteId, { fechaVencimientoFase }),
    onSuccess: () => {
      setModalOpen(false);
      void queryClient.invalidateQueries({ queryKey: ['contratacion', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expediente', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
      void queryClient.invalidateQueries({ queryKey: ['documentacion-fase', expedienteId] });
    },
  });

  if (!data || data.faseNegocio !== 'contratacion') {
    return null;
  }

  const puedeAvanzar = data.puedeAvanzarFase2 ?? data.contratacionCompletada;

  return (
    <div className="flex flex-wrap items-center gap-2">
      <EnlaceClienteModal expedienteId={expedienteId} accessUrl={data.accessUrl} />
      <Button
        size="sm"
        disabled={!puedeAvanzar || avanzarMutation.isPending}
        onClick={() => setModalOpen(true)}
      >
        Pasar a Fase 2
      </Button>
      <PasarFaseDuracionModal
        open={modalOpen}
        onOpenChange={setModalOpen}
        faseDestinoNumero={2}
        faseDestinoLabel="Documentación"
        confirmLabel="Pasar a Fase 2"
        pending={avanzarMutation.isPending}
        error={avanzarMutation.error?.message ?? null}
        onConfirm={(fechaVencimientoFase) => avanzarMutation.mutate(fechaVencimientoFase)}
      />
    </div>
  );
}

function DocumentacionToolbarActions({ expedienteId }: { expedienteId: string }) {
  const queryClient = useQueryClient();
  const [mostrarAddDoc, setMostrarAddDoc] = useState(false);
  const [modalOpen, setModalOpen] = useState(false);

  const { data } = useQuery({
    queryKey: ['documentacion-fase', expedienteId],
    queryFn: () => api.getDocumentacion(expedienteId),
    refetchInterval: 8000,
  });

  const agregarMutation = useMutation({
    mutationFn: (values: DocumentoRequeridoFormValues) =>
      api.agregarDocumentoDocumentacion(expedienteId, values),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['documentacion-fase', expedienteId] });
      setMostrarAddDoc(false);
    },
  });

  const avanzarMutation = useMutation({
    mutationFn: (fechaVencimientoFase: string) =>
      api.avanzarTramitacion(expedienteId, { fechaVencimientoFase }),
    onSuccess: () => {
      setModalOpen(false);
      void queryClient.invalidateQueries({ queryKey: ['documentacion-fase', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expediente', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
      void queryClient.invalidateQueries({ queryKey: ['tramitacion', expedienteId] });
    },
  });

  if (!data) {
    return null;
  }

  return (
    <>
      <div className="flex flex-wrap items-center justify-end gap-2">
        <EnlaceClienteModal expedienteId={expedienteId} accessUrl={data.accessUrl} />
        <Button variant="outline" size="sm" onClick={() => setMostrarAddDoc(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Añadir documento
        </Button>
        <Button
          size="sm"
          disabled={!data.puedeAvanzarFase3 || avanzarMutation.isPending}
          onClick={() => setModalOpen(true)}
        >
          Pasar a Fase 3
        </Button>
      </div>

      <PasarFaseDuracionModal
        open={modalOpen}
        onOpenChange={setModalOpen}
        faseDestinoNumero={3}
        faseDestinoLabel="Tramitación"
        confirmLabel="Pasar a Fase 3"
        pending={avanzarMutation.isPending}
        error={avanzarMutation.error?.message ?? null}
        onConfirm={(fechaVencimientoFase) => avanzarMutation.mutate(fechaVencimientoFase)}
      />

      <DocumentoRequeridoFormModal
        open={mostrarAddDoc}
        mode="create"
        isPending={agregarMutation.isPending}
        onOpenChange={setMostrarAddDoc}
        onSubmit={(values) => agregarMutation.mutate(values)}
      />
    </>
  );
}
