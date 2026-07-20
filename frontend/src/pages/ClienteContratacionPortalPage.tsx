import { useMemo } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  AlertTriangle,
  Clock,
  CreditCard,
  Loader2,
  PenLine,
  User,
} from 'lucide-react';
import { api, type AccesoExpedienteResponse, type AccesoPasoResponse } from '@/api/client';
import { DocumentoUploadPanel } from '@/components/cliente-portal/DocumentoUploadPanel';
import { RequerimientosClientePortal } from '@/components/cliente-portal/RequerimientosClientePortal';
import { FirmaDocumentoWizard } from '@/components/cliente-portal/FirmaDocumentoWizard';
import { PortalClienteShell } from '@/components/cliente-portal/PortalClienteShell';
import { PortalClienteBrandingHero } from '@/components/cliente-portal/PortalClienteBrandingHero';
import { ClienteIdentidadOnboarding } from '@/components/documento-identidad/ClienteIdentidadOnboarding';
import { Button } from '@/components/ui/button';
import { useMercureAcceso } from '@/hooks/useMercureAcceso';
import { CalendarioCuotasTable } from '@/components/expedientes/contratacion/CondicionesPagoPanel';
import { formatEuros, getImportePagoInicial } from '@/lib/pago-contratacion';
import { cn } from '@/lib/utils';

interface ClienteContratacionPortalPageProps {
  token: string;
}

const PASO_ICONS: Record<string, typeof User> = {
  datos_cliente: User,
  firmas: PenLine,
  pago: CreditCard,
};

export function ClienteContratacionPortalPage({ token }: ClienteContratacionPortalPageProps) {
  const queryClient = useQueryClient();
  useMercureAcceso(token);

  const { data, isLoading, error } = useQuery({
    queryKey: ['acceso', token],
    queryFn: () => api.getAccesoExpediente(token),
    retry: false,
    refetchInterval: (query) => {
      const fase = query.state.data?.faseNegocio;
      return fase === 'contratacion' || fase === 'requerimientos' ? 8000 : false;
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

  if (data.faseNegocio === 'requerimientos') {
    return (
      <PortalClienteShell data={data}>
        <RequerimientosClientePortal token={token} data={data} />
      </PortalClienteShell>
    );
  }

  if (data.faseNegocio !== 'contratacion') {
    return (
      <PortalClienteShell data={data}>
        <p className="text-center text-sm text-muted-foreground py-8">
          Este expediente no est? disponible en el portal en este momento.
        </p>
      </PortalClienteShell>
    );
  }

  return (
    <PortalClienteShell data={data}>
      {esperandoAbogado ? (
        <WaitingScreen />
      ) : pasoActivo ? (
        <PasoActivoContent
          token={token}
          paso={pasoActivo}
          data={data}
          onCompletar={(p) => completarMutation.mutate(p)}
          onIdentidadCompletada={() =>
            void queryClient.invalidateQueries({ queryKey: ['acceso', token] })
          }
          onIniciarPago={() => pagoMutation.mutate()}
          completando={completarMutation.isPending}
          iniciandoPago={pagoMutation.isPending}
        />
      ) : (
        <div className="py-8 text-center text-sm text-muted-foreground">
          Todos los pasos han sido completados. Su abogado finalizar? la contrataci?n.
        </div>
      )}
    </PortalClienteShell>
  );
}

function WaitingScreen() {
  return (
    <div className="flex flex-col items-center py-10 text-center">
      <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
        <Clock className="h-7 w-7 text-amber-600 animate-pulse" />
      </div>
      <h2 className="text-lg font-semibold">Esperando revisi?n del abogado</h2>
      <p className="mt-2 max-w-sm text-sm text-muted-foreground">
        Su abogado est? revisando la informaci?n enviada. Podr? continuar cuando le avisemos.
      </p>
    </div>
  );
}

function NotaDevolucionBanner({ nota }: { nota: string }) {
  return (
    <div className="mb-5 flex gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm">
      <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />
      <div>
        <p className="font-semibold text-amber-900">Su abogado le ha enviado un mensaje sobre este paso</p>
        <p className="mt-1 whitespace-pre-wrap text-amber-800">{nota}</p>
      </div>
    </div>
  );
}

function PasoActivoContent({
  token,
  paso,
  data,
  onCompletar,
  onIdentidadCompletada,
  onIniciarPago,
  completando,
  iniciandoPago,
}: {
  token: string;
  paso: AccesoPasoResponse;
  data: AccesoExpedienteResponse;
  onCompletar: (paso: string) => void;
  onIdentidadCompletada: () => void;
  onIniciarPago: () => void;
  completando: boolean;
  iniciandoPago: boolean;
}) {
  const Icon = PASO_ICONS[paso.paso] ?? User;

  const docsObligatoriosOk =
    paso.paso !== 'datos_cliente' ||
    (data.documentosRequeridos ?? [])
      .filter((d) => d.obligatorio)
      .every((d) => d.estado === 'entregado' || d.estado === 'validado');

  const firmasOk =
    paso.paso !== 'firmas' || (data.documentosFirma ?? []).every((d) => d.firmado);

  const puedeConfirmar = docsObligatoriosOk && firmasOk && paso.paso !== 'datos_cliente';

  const docsAdicionales = (data.documentosRequeridos ?? []).filter((d) => d.obligatorio);

  if (paso.paso === 'datos_cliente') {
    return (
      <div>
        {paso.notaDevolucion && <NotaDevolucionBanner nota={paso.notaDevolucion} />}
        <PasoTitulo icon={Icon} label={paso.label} />
        <ClienteIdentidadOnboarding
          token={token}
          tipoServicio={data.tipoServicio}
          identidadEdicion={data.identidadEdicion}
          datosClienteEditables={data.datosClienteEditables}
          notaDevolucion={paso.notaDevolucion}
          onCompletado={onIdentidadCompletada}
        />
        {docsAdicionales.length > 0 && (
          <div className="mt-6 border-t pt-6">
            <p className="section-label mb-3">Documentaci?n adicional</p>
            <DocumentoUploadPanel token={token} documentos={data.documentosRequeridos ?? []} />
          </div>
        )}
      </div>
    );
  }

  return (
    <div>
      {paso.notaDevolucion && <NotaDevolucionBanner nota={paso.notaDevolucion} />}
      <PasoTitulo icon={Icon} label={paso.label} />

      {paso.paso === 'firmas' && (
        <FirmaDocumentoWizard
          token={token}
          documentos={data.documentosFirma ?? []}
          firmasConfig={data.firmas}
        />
      )}

      {paso.paso === 'pago' && data.resumenPago && (
        <PagoResumen resumen={data.resumenPago} onIniciarPago={onIniciarPago} iniciando={iniciandoPago} />
      )}

      {paso.paso === 'pago' && data.resumenPago?.metodoPago === 'manual' && (
        <div className="mt-6 rounded-lg border border-border bg-muted/20 p-4 text-sm text-muted-foreground">
          <p className="font-medium text-foreground">?C?mo funciona el pago manual?</p>
          <p className="mt-2">
            Realice el pago inicial seg?n las instrucciones anteriores (transferencia, Bizum o el medio acordado con su
            abogado). <strong>No debe confirmar nada en este portal</strong>: su abogado validar? el cobro cuando lo
            reciba.
          </p>
        </div>
      )}

      {paso.paso !== 'pago' && (
        <>
          {!firmasOk && paso.paso === 'firmas' && (
            <p className="mt-4 text-xs text-muted-foreground">
              Complete la verificaci?n SMS y firme los tres documentos para continuar.
            </p>
          )}

          <Button
            className="mt-4 w-full"
            size="lg"
            onClick={() => onCompletar(paso.paso)}
            disabled={!puedeConfirmar || completando}
          >
            {completando ? 'Enviando?' : 'Confirmar y continuar'}
          </Button>
        </>
      )}
    </div>
  );
}

function PasoTitulo({ icon: Icon, label }: { icon: typeof User; label: string }) {
  return (
    <div className="mb-5 flex items-center gap-3 border-b border-border pb-4">
      <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
        <Icon className="h-5 w-5" />
      </div>
      <div className="min-w-0">
        <p className="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Paso actual</p>
        <h2 className="text-lg font-semibold leading-tight">{label}</h2>
      </div>
    </div>
  );
}

function PagoResumen({
  resumen,
  onIniciarPago,
  iniciando,
}: {
  resumen: NonNullable<AccesoExpedienteResponse['resumenPago']>;
  onIniciarPago: () => void;
  iniciando: boolean;
}) {
  const calendario = resumen.calendarioPago ?? resumen.calendarioProyectado ?? [];
  const calendarioDefinitivo = !!resumen.calendarioPago && !!resumen.fechaFirmaContrato;
  const importePagoInicial = getImportePagoInicial(resumen);
  const planLabel =
    resumen.planPago === 'fraccionado'
      ? `Fraccionado (${resumen.numCuotas} cuotas)`
      : 'Pago ?nico';
  const esManual = resumen.metodoPago === 'manual';

  return (
    <div className="space-y-4">
      <div className="space-y-3 rounded-lg border bg-card p-4 text-sm">
        <DatoFila
          label={resumen.planPago === 'fraccionado' ? 'Pago inicial (1.? cuota)' : 'Importe a pagar'}
          value={formatEuros(importePagoInicial)}
          destacado
        />
        {resumen.planPago === 'fraccionado' && (
          <DatoFila
            label="Honorarios totales"
            value={formatEuros(resumen.honorariosAcordados)}
          />
        )}
        <DatoFila label="M?todo" value={resumen.metodoPagoLabel} />
        <DatoFila label="Plan" value={resumen.planPagoLabel ?? planLabel} />
        {esManual && resumen.iban && (
          <>
            <DatoFila label="Titular" value={resumen.titularCuenta} />
            <DatoFila label="IBAN" value={resumen.iban} />
          </>
        )}
        {resumen.metodoPago === 'digital' && (
          <Button className="mt-2 w-full" size="lg" onClick={onIniciarPago} disabled={iniciando}>
            {iniciando ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Redirigiendo?
              </>
            ) : (
              'Pagar ahora'
            )}
          </Button>
        )}
      </div>

      {calendario.length > 0 && (
        <CalendarioCuotasTable
          cuotas={calendario}
          definitivo={calendarioDefinitivo}
          fechaFirmaContrato={resumen.fechaFirmaContrato}
        />
      )}
    </div>
  );
}

function DatoFila({ label, value, destacado }: { label: string; value: string; destacado?: boolean }) {
  return (
    <div className="flex justify-between gap-4">
      <span className="text-muted-foreground">{label}</span>
      <span className={cn('text-right', destacado && 'font-bold text-primary')}>{value}</span>
    </div>
  );
}
