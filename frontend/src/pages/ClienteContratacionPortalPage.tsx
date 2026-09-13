import { useCallback, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/api/client';
import { ContratacionJourney } from '@/components/cliente-portal/contratacion/ContratacionJourney';
import { PortalClienteShell } from '@/components/cliente-portal/PortalClienteShell';
import { PortalClienteBrandingHero } from '@/components/cliente-portal/PortalClienteBrandingHero';
import { DocumentacionClientePortal } from '@/components/cliente-portal/DocumentacionClientePortal';
import { TramitacionClientePortal } from '@/components/cliente-portal/TramitacionClientePortal';
import { ResolucionClientePortal } from '@/components/cliente-portal/ResolucionClientePortal';
import { useMercureAcceso } from '@/hooks/useMercureAcceso';

interface ClienteContratacionPortalPageProps {
  token: string;
}

export function ClienteContratacionPortalPage({ token }: ClienteContratacionPortalPageProps) {
  const queryClient = useQueryClient();
  const [focusMode, setFocusMode] = useState(false);
  useMercureAcceso(token);

  const { data, isLoading, error } = useQuery({
    queryKey: ['acceso', token],
    queryFn: () => api.getAccesoExpediente(token),
    retry: false,
    refetchInterval: (query) => {
      const fase = query.state.data?.faseNegocio;
      return fase === 'contratacion' ||
        fase === 'documentacion' ||
        fase === 'tramitacion' ||
        fase === 'resolucion'
        ? 8000
        : false;
    },
  });

  const completarMutation = useMutation({
    mutationFn: (paso: string) => api.completarPasoCliente(token, paso),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['acceso', token] }),
  });

  const pagoMutation = useMutation({
    mutationFn: () => api.iniciarPagoAcceso(token),
    onSuccess: (result) => {
      window.location.href = result.checkoutUrl;
    },
  });

  const esperandoAbogado = useMemo(() => {
    if (!data?.pasos?.length) return false;
    return !data.pasoActivo && data.pasos.some((p) => p.estado === 'realizado_cliente');
  }, [data]);

  const pasoActivo = useMemo(() => {
    if (!data?.pasos?.length || !data.pasoActivo) return null;
    return data.pasos.find((p) => p.paso === data.pasoActivo) ?? null;
  }, [data]);

  const onFocusChange = useCallback((isFocus: boolean) => {
    setFocusMode(isFocus);
  }, []);

  if (isLoading) {
    return (
      <div className="min-h-screen bg-muted/30">
        <PortalClienteBrandingHero compact />
        <div className="flex items-center justify-center px-4 py-16">
          <p className="text-muted-foreground">Cargando su expediente…</p>
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="min-h-screen bg-muted/30">
        <PortalClienteBrandingHero compact />
        <div className="mx-auto max-w-md px-4 py-10">
          <div className="panel p-8 text-center">
            <p className="font-medium text-destructive">Enlace no válido</p>
            <p className="mt-2 text-sm text-muted-foreground">
              Este enlace de acceso no es válido o ha expirado. Contacte con su abogado.
            </p>
          </div>
        </div>
      </div>
    );
  }

  const fasePortal = (data.faseNegocio as string) === 'requerimientos' ? 'documentacion' : data.faseNegocio;

  if (fasePortal === 'documentacion') {
    return (
      <PortalClienteShell data={data}>
        <DocumentacionClientePortal token={token} data={data} />
      </PortalClienteShell>
    );
  }

  if (data.faseNegocio === 'tramitacion') {
    return (
      <PortalClienteShell data={data}>
        <TramitacionClientePortal token={token} data={data} />
      </PortalClienteShell>
    );
  }

  if (data.faseNegocio === 'resolucion') {
    return (
      <PortalClienteShell data={data}>
        <ResolucionClientePortal data={data} />
      </PortalClienteShell>
    );
  }

  if (data.faseNegocio !== 'contratacion') {
    return (
      <PortalClienteShell data={data}>
        <p className="py-8 text-center text-sm text-muted-foreground">
          Este expediente no está disponible en el portal en este momento.
        </p>
      </PortalClienteShell>
    );
  }

  return (
    <PortalClienteShell data={data} focusMode={focusMode}>
      <div className="flex min-h-0 min-w-0 flex-1 flex-col">
        <ContratacionJourney
          token={token}
          data={data}
          pasoActivo={pasoActivo}
          esperandoAbogado={esperandoAbogado}
          onCompletar={(p) => completarMutation.mutate(p)}
          onIdentidadCompletada={() =>
            void queryClient.invalidateQueries({ queryKey: ['acceso', token] })
          }
          onIniciarPago={() => pagoMutation.mutate()}
          completando={completarMutation.isPending}
          iniciandoPago={pagoMutation.isPending}
          onFocusChange={onFocusChange}
        />
      </div>
    </PortalClienteShell>
  );
}
