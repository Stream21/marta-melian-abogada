import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMercureContratacion } from '@/hooks/useMercureContratacion';
import {
  CheckCircle2,
  Circle,
  Clock,
  CreditCard,
  FileText,
  PenLine,
  Radio,
  Upload,
  User,
} from 'lucide-react';
import { api, type ContratacionPasoResponse, type ContratacionResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { EnlaceClienteModal } from './EnlaceClienteModal';

const PASO_ICONS: Record<string, typeof Upload> = {
  documentacion: Upload,
  datos_cliente: User,
  firmas: PenLine,
  pago: CreditCard,
};

interface ContratacionGestionPanelProps {
  expedienteId: string;
}

function estadoBadge(estado: string) {
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
  metodoPagoLabel,
  onValidar,
  validando,
}: {
  paso: ContratacionPasoResponse;
  metodoPagoLabel: string;
  onValidar: (paso: string) => void;
  validando: boolean;
}) {
  const Icon = PASO_ICONS[paso.paso] ?? FileText;
  const esPago = paso.paso === 'pago';

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
            {estadoBadge(paso.estado)}
          </div>
          <p className="mt-1 text-sm text-muted-foreground">{paso.descripcion}</p>

          {esPago && (
            <p className="mt-2 text-xs text-muted-foreground">
              Método acordado: <strong>{metodoPagoLabel}</strong>. El abogado debe confirmar que el
              pago se ha efectuado correctamente.
            </p>
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
              onClick={() => onValidar(paso.paso)}
              disabled={validando}
            >
              <CheckCircle2 className="mr-2 h-4 w-4" />
              {esPago ? 'Confirmar pago recibido' : 'Validar y continuar'}
            </Button>
          )}
        </div>
      </div>
    </div>
  );
}

function StepperHorizontal({ pasos, pasoActivo }: { pasos: ContratacionPasoResponse[]; pasoActivo?: string }) {
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
  });

  const validarMutation = useMutation({
    mutationFn: (paso: string) => api.validarPasoContratacion(expedienteId, paso),
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
      <div className="panel p-8 text-center">
        <p className="text-muted-foreground">
          Este expediente ya no está en fase de contratación ({data.faseNegocioLabel}).
        </p>
      </div>
    );
  }

  return (
    <ContratacionContent
      data={data}
      onValidar={(paso) => validarMutation.mutate(paso)}
      validando={validarMutation.isPending}
    />
  );
}

function ContratacionContent({
  data,
  onValidar,
  validando,
}: {
  data: ContratacionResponse;
  onValidar: (paso: string) => void;
  validando: boolean;
}) {
  const pendientesRevision = data.pasos.filter((p) => p.requiereValidacionAbogado).length;

  return (
    <div className="space-y-6">
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
            <Badge variant="secondary" className="gap-1">
              <Radio className="h-3 w-3 text-emerald-600" />
              Tiempo real
            </Badge>
            <EnlaceClienteModal accessUrl={data.accessUrl} />
          </div>
        </div>

        <StepperHorizontal pasos={data.pasos} pasoActivo={data.pasoActivo} />

        <div className="grid gap-4">
          {data.pasos.map((paso) => (
            <PasoCard
              key={paso.paso}
              paso={paso}
              metodoPagoLabel={data.metodoPagoLabel}
              onValidar={onValidar}
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

      <div className="panel p-6">
        <h3 className="section-label mb-4">Línea de tiempo</h3>
        {data.hitos.length === 0 ? (
          <p className="text-sm text-muted-foreground">Sin actividad registrada aún.</p>
        ) : (
          <ul className="space-y-3">
            {data.hitos.map((hito) => (
              <li key={hito.id} className="flex gap-3 text-sm">
                <div className="mt-0.5 text-muted-foreground">
                  {hito.actor === 'cliente' ? (
                    <User className="h-4 w-4" />
                  ) : hito.actor === 'abogado' ? (
                    <CheckCircle2 className="h-4 w-4 text-primary" />
                  ) : (
                    <Circle className="h-4 w-4" />
                  )}
                </div>
                <div>
                  <p>{hito.descripcion}</p>
                  <p className="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                    <Clock className="h-3 w-3" />
                    {new Date(hito.createdAt).toLocaleString('es-ES')}
                    <span className="capitalize">· {hito.actor}</span>
                  </p>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>

      <div className="grid gap-3 sm:grid-cols-3 text-sm">
        <div className="rounded-lg border border-border p-4">
          <p className="section-label">Honorarios</p>
          <p className="font-bold text-primary mt-1">
            {data.honorariosAcordados.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €
          </p>
        </div>
        <div className="rounded-lg border border-border p-4">
          <p className="section-label">Método de pago</p>
          <p className="font-medium mt-1">{data.metodoPagoLabel}</p>
        </div>
        <div className="rounded-lg border border-border p-4">
          <p className="section-label">Plan</p>
          <p className="font-medium mt-1">
            {data.planPago === 'fraccionado'
              ? `Fraccionado (${data.numCuotas} cuotas)`
              : 'Pago único'}
          </p>
        </div>
      </div>
    </div>
  );
}
