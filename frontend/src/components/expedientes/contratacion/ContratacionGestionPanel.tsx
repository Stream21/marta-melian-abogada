import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMercureContratacion } from '@/hooks/useMercureContratacion';
import { ContratacionRevisionModal } from './ContratacionRevisionModal';
import {
  CheckCircle2,
  Circle,
  Clock,
  ChevronDown,
  CreditCard,
  FileText,
  PenLine,
  Radio,
  User,
  AlertTriangle,
} from 'lucide-react';
import { api, type ContratacionPasoResponse, type ContratacionResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { EnlaceClienteModal } from './EnlaceClienteModal';
import { CondicionesPagoPanel } from './CondicionesPagoPanel';
import { ContratacionTimelinePanel } from './ContratacionTimelinePanel';
import { RequerimientosEnConstruccionPanel } from './RequerimientosEnConstruccionPanel';
import {
  contarFirmasCompletadas,
  FirmasProgresoResumen,
} from './FirmasRevisionPanel';
import type { ContratacionFirmaDocumentoResponse } from '@/api/client';

const PASO_ICONS: Record<string, typeof User> = {
  datos_cliente: User,
  firmas: PenLine,
  pago: CreditCard,
};

interface ContratacionGestionPanelProps {
  expedienteId: string;
}

function vencimientoBadge(fecha: string | null | undefined) {
  if (!fecha) return null;
  const venc = new Date(fecha + 'T23:59:59');
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  const dias = Math.ceil((venc.getTime() - hoy.getTime()) / 86400000);
  if (dias < 0) {
    return (
      <Badge variant="destructive" className="gap-1">
        <AlertTriangle className="h-3 w-3" />
        Vencido hace {Math.abs(dias)} día(s)
      </Badge>
    );
  }
  if (dias <= 7) {
    return (
      <Badge variant="warning" className="gap-1">
        <AlertTriangle className="h-3 w-3" />
        Vence en {dias} día(s)
      </Badge>
    );
  }
  return (
    <Badge variant="secondary" className="gap-1">
      <Clock className="h-3 w-3" />
      Vence {venc.toLocaleDateString('es-ES')}
    </Badge>
  );
}

function estadoBadge(estado: string, esPagoManualPendiente = false) {
  if (esPagoManualPendiente) {
    return <Badge variant="warning">Pendiente de cobro</Badge>;
  }

  switch (estado) {
    case 'validado_abogado':
      return <Badge variant="success">Validado</Badge>;
    case 'realizado_cliente':
      return <Badge variant="warning">Pendiente de revisión</Badge>;
    default:
      return <Badge variant="secondary">Pendiente cliente</Badge>;
  }
}

function PasoCard({
  paso,
  metodoPago,
  metodoPagoLabel,
  firmasDocumento,
  onRevisar,
  validando,
}: {
  paso: ContratacionPasoResponse;
  metodoPago: string;
  metodoPagoLabel: string;
  firmasDocumento?: ContratacionFirmaDocumentoResponse[];
  onRevisar: (paso: ContratacionPasoResponse) => void;
  validando: boolean;
}) {
  const Icon = PASO_ICONS[paso.paso] ?? FileText;
  const esPago = paso.paso === 'pago';
  const esFirmas = paso.paso === 'firmas';
  const esPagoManualPendiente = esPago && metodoPago === 'manual' && paso.estado === 'pendiente';
  const firmasEnCurso =
    esFirmas && (paso.estado === 'pendiente' || paso.estado === 'realizado_cliente');
  const firmasCompletadas = contarFirmasCompletadas(firmasDocumento);

  return (
    <div
      className={cn(
        'rounded-xl border p-5 transition-colors',
        paso.estado === 'validado_abogado' && 'border-emerald-200 bg-emerald-50/50',
        paso.estado === 'realizado_cliente' && 'border-amber-200 bg-amber-50/30',
        paso.estado === 'pendiente' && 'border-border bg-card',
      )}
    >
      <div className="flex items-start gap-4">
        <div
          className={cn(
            'flex h-11 w-11 shrink-0 items-center justify-center rounded-lg',
            paso.estado === 'validado_abogado' && 'bg-emerald-100 text-emerald-700',
            paso.estado === 'realizado_cliente' && 'bg-amber-100 text-amber-700',
            paso.estado === 'pendiente' && 'bg-muted text-muted-foreground',
          )}
        >
          <Icon className="h-5 w-5" />
        </div>

        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h3 className="font-semibold">{paso.label}</h3>
            <div className="flex flex-wrap items-center gap-2">
              {esFirmas && firmasEnCurso && (
                <Badge variant={firmasCompletadas === 3 ? 'success' : 'info'}>
                  {firmasCompletadas}/3 firmados
                </Badge>
              )}
              {estadoBadge(paso.estado, esPagoManualPendiente)}
            </div>
          </div>
          <p className="mt-1 text-sm text-muted-foreground">{paso.descripcion}</p>

          {esPago && (
            <p className="mt-2 text-xs text-muted-foreground">
              Método acordado: <strong>{metodoPagoLabel}</strong>.
              {metodoPago === 'manual'
                ? ' Confirme usted que ha recibido el cobro inicial (efectivo, Bizum, transferencia, etc.).'
                : ' El abogado debe confirmar que el pago se ha efectuado correctamente.'}
            </p>
          )}

          {esFirmas && firmasEnCurso && (firmasDocumento?.length ?? 0) > 0 && (
            <div className="mt-4">
              <FirmasProgresoResumen firmas={firmasDocumento} compact />
            </div>
          )}

          {paso.realizadoAt && (
            <p className="mt-2 text-xs text-muted-foreground">
              Cliente completó: {new Date(paso.realizadoAt).toLocaleString('es-ES')}
            </p>
          )}
          {paso.validadoAt && (
            <p className="text-xs text-emerald-700">
              Validado: {new Date(paso.validadoAt).toLocaleString('es-ES')}
            </p>
          )}

          {paso.requiereValidacionAbogado && (
            <Button
              size="sm"
              className="mt-4"
              onClick={() => onRevisar(paso)}
              disabled={validando}
            >
              <CheckCircle2 className="mr-2 h-4 w-4" />
              {esPago ? 'Revisar pago' : 'Revisar paso'}
            </Button>
          )}

          {paso.estado === 'pendiente' && paso.notaDevolucion && (
            <p className="mt-3 text-xs text-amber-700 bg-amber-50 rounded-md p-2 border border-amber-200">
              Devuelto al cliente: «{paso.notaDevolucion}»
            </p>
          )}
        </div>
      </div>
    </div>
  );
}

function StepperHorizontal({
  pasos,
  pasoActivo,
  firmasDocumento,
}: {
  pasos: ContratacionPasoResponse[];
  pasoActivo?: string;
  firmasDocumento?: ContratacionFirmaDocumentoResponse[];
}) {
  const firmasCompletadas = contarFirmasCompletadas(firmasDocumento);

  return (
    <div className="mb-8 flex items-center gap-2 overflow-x-auto pb-2">
      {pasos.map((paso, i) => {
        const activo = paso.paso === pasoActivo;
        const completado = paso.estado === 'validado_abogado';
        const enRevision = paso.estado === 'realizado_cliente';

        return (
          <div key={paso.paso} className="flex items-center">
            <div className="flex flex-col items-center gap-1 min-w-[100px]">
              <div
                className={cn(
                  'flex h-9 w-9 items-center justify-center rounded-full border-2 text-sm font-bold',
                  completado && 'border-emerald-500 bg-emerald-500 text-white',
                  enRevision && 'border-amber-500 bg-amber-100 text-amber-800',
                  activo && !completado && !enRevision && 'border-primary bg-primary/10 text-primary',
                  !activo && !completado && !enRevision && 'border-border text-muted-foreground',
                )}
              >
                {completado ? <CheckCircle2 className="h-4 w-4" /> : paso.orden}
              </div>
              <span className="text-center text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
                {paso.label}
              </span>
              {paso.paso === 'firmas' && paso.paso === pasoActivo && paso.estado === 'pendiente' && (
                <span className="text-[10px] font-semibold text-primary">{firmasCompletadas}/3</span>
              )}
            </div>
            {i < pasos.length - 1 && (
              <div className={cn('mx-1 h-0.5 w-8', completado ? 'bg-emerald-400' : 'bg-border')} />
            )}
          </div>
        );
      })}
    </div>
  );
}

export function ContratacionGestionPanel({ expedienteId }: ContratacionGestionPanelProps) {
  const queryClient = useQueryClient();
  useMercureContratacion(expedienteId);

  const { data, isLoading, error } = useQuery({
    queryKey: ['contratacion', expedienteId],
    queryFn: () => api.getContratacion(expedienteId),
    refetchInterval: 8000,
    staleTime: 0,
  });

  const validarMutation = useMutation({
    mutationFn: (paso: string) => api.validarPasoContratacion(expedienteId, paso),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['contratacion', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
    },
  });

  const devolverMutation = useMutation({
    mutationFn: ({ paso, nota }: { paso: string; nota: string }) =>
      api.devolverPasoContratacion(expedienteId, paso, nota),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['contratacion', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
    },
  });

  if (isLoading) {
    return <p className="text-muted-foreground py-8 text-center">Cargando contratación…</p>;
  }

  if (error || !data) {
    return (
      <p className="text-destructive py-8 text-center">
        No se pudo cargar la fase de contratación.
      </p>
    );
  }

  if (data.faseNegocio !== 'contratacion') {
    return (
      <RequerimientosEnConstruccionPanel
        expedienteId={expedienteId}
        numero={data.numero}
      />
    );
  }

  return (
    <ContratacionContent
      expedienteId={expedienteId}
      data={data}
      onValidar={(paso) => validarMutation.mutate(paso)}
      onDevolver={(paso, nota) => devolverMutation.mutate({ paso, nota })}
      validando={validarMutation.isPending}
      devolviendo={devolverMutation.isPending}
      errorAccion={validarMutation.error?.message ?? devolverMutation.error?.message ?? null}
    />
  );
}

function ContratacionContent({
  expedienteId,
  data,
  onValidar,
  onDevolver,
  validando,
  devolviendo,
  errorAccion,
}: {
  expedienteId: string;
  data: ContratacionResponse;
  onValidar: (paso: string) => void;
  onDevolver: (paso: string, nota: string) => void;
  validando: boolean;
  devolviendo: boolean;
  errorAccion: string | null;
}) {
  const [pasoRevision, setPasoRevision] = useState<ContratacionPasoResponse | null>(null);
  const pendientesRevision = data.pasos.filter((p) => p.requiereValidacionAbogado).length;

  return (
    <div className="space-y-6">
      <ContratacionRevisionModal
        expedienteId={expedienteId}
        paso={pasoRevision}
        open={pasoRevision !== null}
        onClose={() => setPasoRevision(null)}
        onValidar={(paso) => {
          onValidar(paso);
          setPasoRevision(null);
        }}
        onDevolver={(paso, nota) => {
          onDevolver(paso, nota);
          setPasoRevision(null);
        }}
        validando={validando}
        devolviendo={devolviendo}
        errorAccion={errorAccion}
      />
      <div className="panel p-6">
        <div className="flex flex-wrap items-start justify-between gap-4 mb-6">
          <div>
            <p className="section-label">Fase 1</p>
            <h2 className="panel-title">Contratación — Formalización inicial</h2>
            <p className="text-sm text-muted-foreground mt-1">
              Supervise el avance del cliente y valide cada hito antes de pasar a requerimientos.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Badge variant={pendientesRevision > 0 ? 'warning' : 'info'}>
              {pendientesRevision > 0 ? `${pendientesRevision} pendiente(s) de revisión` : 'En proceso'}
            </Badge>
            {vencimientoBadge(data.fechaVencimientoFase)}
            <Badge variant="secondary" className="gap-1">
              <Radio className="h-3 w-3 text-emerald-600" />
              Tiempo real
            </Badge>
            <EnlaceClienteModal expedienteId={expedienteId} accessUrl={data.accessUrl} />
          </div>
        </div>

        <StepperHorizontal
          pasos={data.pasos}
          pasoActivo={data.pasoActivo ?? undefined}
          firmasDocumento={data.firmasDocumento}
        />

        <div className="grid gap-4">
          {data.pasos.map((paso) => (
            <PasoCard
              key={paso.paso}
              paso={paso}
              metodoPago={data.metodoPago}
              metodoPagoLabel={data.metodoPagoLabel}
              firmasDocumento={data.firmasDocumento}
              onRevisar={setPasoRevision}
              validando={validando}
            />
          ))}
        </div>

        {data.contratacionCompletada && (
          <div className="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p className="text-sm font-medium text-emerald-800">
              Contratación completada. El expediente puede pasar a la fase de requerimientos.
            </p>
          </div>
        )}
      </div>

      <CondicionesPagoPanel expedienteId={expedienteId} data={data} />

      {(data.firmasDocumento?.some((f) => f.firmado) ?? false) && (
        <details className="group panel overflow-hidden" open={false}>
          <summary className="flex cursor-pointer list-none items-center justify-between gap-2 p-6 [&::-webkit-details-marker]:hidden">
            <h3 className="section-label">Integridad de documentos firmados</h3>
            <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" />
          </summary>
          <div className="border-t border-border px-6 pb-6">
            <ul className="space-y-3 pt-4">
              {data.firmasDocumento!
                .filter((firma) => firma.firmado)
                .map((firma) => (
                  <li
                    key={firma.tipo}
                    className="rounded-lg border border-border bg-card px-4 py-3 text-sm space-y-2"
                  >
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <span className="font-medium">{firma.label}</span>
                      {firma.integridadOk === true && (
                        <Badge variant="success">Integridad verificada</Badge>
                      )}
                      {firma.integridadOk === false && (
                        <Badge variant="destructive">Archivo alterado</Badge>
                      )}
                      {firma.integridadOk === null && (
                        <Badge variant="secondary">Sin huella registrada</Badge>
                      )}
                    </div>
                    {firma.pdfSha256 && (
                      <p className="text-[11px] text-muted-foreground font-mono break-all">
                        SHA-256: {firma.pdfSha256}
                      </p>
                    )}
                    {firma.firmadoAt && (
                      <p className="text-xs text-muted-foreground">
                        Firmado: {new Date(firma.firmadoAt).toLocaleString('es-ES')}
                      </p>
                    )}
                  </li>
                ))}
            </ul>
          </div>
        </details>
      )}

      <details className="group panel overflow-hidden" open={false}>
        <summary className="flex cursor-pointer list-none items-center justify-between gap-2 p-6 [&::-webkit-details-marker]:hidden">
          <h3 className="section-label">Línea de tiempo</h3>
          <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" />
        </summary>
        <div className="border-t border-border px-6 pb-6 pt-4">
          <ContratacionTimelinePanel
            expedienteId={expedienteId}
            initialHitos={data.hitos}
            total={data.hitosTotal ?? data.hitos.length}
          />
        </div>
      </details>
    </div>
  );
}
