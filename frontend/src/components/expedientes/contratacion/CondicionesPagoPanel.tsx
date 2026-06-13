import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { CalendarDays, ChevronDown, CreditCard, Landmark, Loader2, Save } from 'lucide-react';
import {
  api,
  type CalendarioCuotaResponse,
  type ContratacionResponse,
  type MetodoPago,
  type PlanPago,
} from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface CondicionesPagoPanelProps {
  expedienteId: string;
  data: ContratacionResponse;
}

const METODOS: { value: MetodoPago; label: string; sub: string; icon: typeof CreditCard }[] = [
  { value: 'manual', label: 'Pago manual', sub: 'Transferencia bancaria', icon: Landmark },
  { value: 'digital', label: 'Pago digital', sub: 'Pasarela Stripe', icon: CreditCard },
];

function proyectarCalendarioLocal(
  honorarios: number,
  planPago: PlanPago,
  numCuotas: number,
): CalendarioCuotaResponse[] {
  if (honorarios <= 0) return [];

  const cuotasEfectivas = planPago === 'unico' ? 1 : Math.max(2, Math.min(4, numCuotas));
  const centimosTotal = Math.round(honorarios * 100);
  const base = Math.floor(centimosTotal / cuotasEfectivas);
  const resto = centimosTotal - base * cuotasEfectivas;
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);

  const cuotas: CalendarioCuotaResponse[] = [];
  for (let i = 0; i < cuotasEfectivas; i++) {
    const centimos = base + (i < resto ? 1 : 0);
    const fecha = new Date(hoy);
    if (i === 0) {
      fecha.setDate(fecha.getDate() + 2);
    } else {
      fecha.setMonth(fecha.getMonth() + i);
    }
    cuotas.push({
      numero: i + 1,
      importe: centimos / 100,
      fechaVencimiento: fecha.toISOString().slice(0, 10),
      estado: 'pendiente',
    });
  }
  return cuotas;
}

export function CondicionesPagoPanel({ expedienteId, data }: CondicionesPagoPanelProps) {
  const queryClient = useQueryClient();
  const editable = data.condicionesPagoEditables ?? false;

  const [metodoPago, setMetodoPago] = useState<MetodoPago>(data.metodoPago);
  const [planPago, setPlanPago] = useState<PlanPago>(data.planPago);
  const [numCuotas, setNumCuotas] = useState(data.numCuotas);
  const [honorarios, setHonorarios] = useState(String(data.honorariosAcordados));
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setMetodoPago(data.metodoPago);
    setPlanPago(data.planPago);
    setNumCuotas(data.numCuotas);
    setHonorarios(String(data.honorariosAcordados));
  }, [data.metodoPago, data.planPago, data.numCuotas, data.honorariosAcordados]);

  const honorariosNum = useMemo(() => {
    const parsed = parseFloat(honorarios.replace(',', '.'));
    return Number.isFinite(parsed) ? parsed : 0;
  }, [honorarios]);

  const calendarioMostrar =
    data.calendarioPago ??
    data.calendarioProyectado ??
    proyectarCalendarioLocal(honorariosNum, planPago, numCuotas);

  const calendarioPreview = editable
    ? proyectarCalendarioLocal(honorariosNum, planPago, numCuotas)
    : calendarioMostrar;

  const calendarioDefinitivo = !!data.calendarioPago && !!data.fechaFirmaContrato;

  const guardarMutation = useMutation({
    mutationFn: () =>
      api.actualizarCondicionesPagoContratacion(expedienteId, {
        metodoPago,
        planPago,
        numCuotas: planPago === 'unico' ? 1 : numCuotas,
        honorariosAcordados: honorariosNum,
      }),
    onSuccess: () => {
      setError(null);
      void queryClient.invalidateQueries({ queryKey: ['contratacion', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
      void queryClient.invalidateQueries({ queryKey: ['expediente', expedienteId] });
    },
    onError: (err: Error) => setError(err.message),
  });

  const hayCambios =
    metodoPago !== data.metodoPago ||
    planPago !== data.planPago ||
    (planPago === 'fraccionado' ? numCuotas : 1) !== data.numCuotas ||
    Math.abs(honorariosNum - data.honorariosAcordados) > 0.001;

  const puedeGuardar = editable && hayCambios && honorariosNum > 0 && !guardarMutation.isPending;

  const planResumen =
    data.planPago === 'fraccionado'
      ? `Fraccionado (${data.numCuotas} cuotas)`
      : 'Pago único';

  return (
    <details className="group panel overflow-hidden" open={false}>
      <summary className="flex cursor-pointer list-none items-center justify-between gap-3 p-6 [&::-webkit-details-marker]:hidden">
        <div className="min-w-0 flex-1">
          <h3 className="section-label">Condiciones de pago</h3>
          <p className="mt-1 text-sm font-medium truncate">
            {data.honorariosAcordados.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €
            <span className="mx-2 text-muted-foreground">·</span>
            {data.metodoPagoLabel}
            <span className="mx-2 text-muted-foreground">·</span>
            {planResumen}
          </p>
        </div>
        <div className="flex shrink-0 items-center gap-2">
          <Badge variant={editable ? 'info' : 'secondary'} className="hidden sm:inline-flex">
            {editable ? 'Editable' : 'Bloqueado'}
          </Badge>
          <ChevronDown className="h-4 w-4 text-muted-foreground transition-transform group-open:rotate-180" />
        </div>
      </summary>

      <div className="border-t border-border px-6 pb-6 pt-4">
        <div className="flex flex-wrap items-start justify-between gap-4 mb-6">
          <div>
            <h4 className="font-semibold">Hoja de encargo y calendario</h4>
            <p className="mt-1 text-sm text-muted-foreground max-w-xl">
              Puede modificar método, plan e importe hasta que el cliente firme el primer documento. Al
              firmar la hoja de encargo se fija el calendario: 1.ª cuota a 2 días de la firma y el resto
              el mismo día de los meses siguientes.
            </p>
          </div>
          <Badge variant={editable ? 'info' : 'secondary'} className="sm:hidden">
            {editable ? 'Editable' : 'Bloqueado (firmas iniciadas)'}
          </Badge>
        </div>

      {editable ? (
        <div className="space-y-6">
          <div>
            <Label htmlFor="honorarios-contratacion" className="section-label">
              Honorarios acordados (€)
            </Label>
            <Input
              id="honorarios-contratacion"
              type="number"
              min="0"
              step="0.01"
              value={honorarios}
              onChange={(e) => setHonorarios(e.target.value)}
              className="mt-2 max-w-xs"
            />
          </div>

          <div>
            <p className="section-label mb-3">Método de pago</p>
            <div className="grid gap-3 sm:grid-cols-2">
              {METODOS.map((m) => {
                const Icon = m.icon;
                const selected = metodoPago === m.value;
                return (
                  <button
                    key={m.value}
                    type="button"
                    onClick={() => setMetodoPago(m.value)}
                    className={cn(
                      'flex items-center gap-3 rounded-lg border-2 p-4 text-left transition-colors',
                      selected ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/30',
                    )}
                  >
                    <Icon className="h-5 w-5 text-primary" />
                    <div>
                      <p className="font-semibold">{m.label}</p>
                      <p className="text-xs text-muted-foreground">{m.sub}</p>
                    </div>
                  </button>
                );
              })}
            </div>
          </div>

          <div>
            <p className="section-label mb-3">Plan de pago</p>
            <div className="mb-4 flex rounded-lg border border-border p-1 w-fit">
              {(['unico', 'fraccionado'] as PlanPago[]).map((plan) => (
                <button
                  key={plan}
                  type="button"
                  onClick={() => {
                    setPlanPago(plan);
                    setNumCuotas(plan === 'unico' ? 1 : Math.max(2, numCuotas));
                  }}
                  className={cn(
                    'rounded-md px-4 py-2 text-sm font-medium transition-colors',
                    planPago === plan
                      ? 'bg-primary text-primary-foreground'
                      : 'text-muted-foreground hover:text-foreground',
                  )}
                >
                  {plan === 'unico' ? 'Pago único' : 'Fraccionado'}
                </button>
              ))}
            </div>

            {planPago === 'fraccionado' && (
              <div className="max-w-md">
                <p className="mb-2 text-sm text-muted-foreground">
                  Cuotas: <strong>{numCuotas}</strong>
                </p>
                <input
                  type="range"
                  min={2}
                  max={4}
                  value={numCuotas}
                  onChange={(e) => setNumCuotas(parseInt(e.target.value, 10))}
                  className="w-full accent-primary"
                />
                <div className="flex justify-between text-xs text-muted-foreground">
                  <span>2</span>
                  <span>4</span>
                </div>
              </div>
            )}
          </div>

          {error && (
            <p className="text-sm text-destructive" role="alert">
              {error}
            </p>
          )}

          <Button onClick={() => guardarMutation.mutate()} disabled={!puedeGuardar}>
            {guardarMutation.isPending ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Guardando…
              </>
            ) : (
              <>
                <Save className="mr-2 h-4 w-4" />
                Guardar condiciones
              </>
            )}
          </Button>
        </div>
      ) : (
        <div className="grid gap-3 sm:grid-cols-3 text-sm mb-6">
          <div className="rounded-lg border border-border p-4">
            <p className="section-label">Honorarios</p>
            <p className="font-bold text-primary mt-1">
              {data.honorariosAcordados.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €
            </p>
          </div>
          <div className="rounded-lg border border-border p-4">
            <p className="section-label">Método</p>
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
      )}

      <CalendarioCuotasTable
        cuotas={calendarioPreview}
        definitivo={calendarioDefinitivo}
        fechaFirmaContrato={data.fechaFirmaContrato}
      />
      </div>
    </details>
  );
}

export function CalendarioCuotasTable({
  cuotas,
  definitivo,
  fechaFirmaContrato,
}: {
  cuotas: CalendarioCuotaResponse[];
  definitivo?: boolean;
  fechaFirmaContrato?: string | null;
}) {
  if (cuotas.length === 0) return null;

  return (
    <div className="mt-6 rounded-lg border border-border overflow-hidden">
      <div className="flex flex-wrap items-center justify-between gap-2 border-b border-border bg-muted/30 px-4 py-3">
        <div className="flex items-center gap-2 text-sm font-medium">
          <CalendarDays className="h-4 w-4 text-primary" />
          Calendario de cuotas
        </div>
        <Badge variant={definitivo ? 'success' : 'warning'}>
          {definitivo ? 'Definitivo' : 'Proyectado (referencia)'}
        </Badge>
      </div>
      {fechaFirmaContrato && (
        <p className="px-4 pt-3 text-xs text-muted-foreground">
          Firma hoja de encargo:{' '}
          {new Date(fechaFirmaContrato).toLocaleString('es-ES')}
        </p>
      )}
      {!definitivo && (
        <p className="px-4 pt-2 text-xs text-muted-foreground">
          Las fechas definitivas se fijan al firmar la hoja de encargo (1.ª cuota con 2 días de margen).
        </p>
      )}
      <ul className="divide-y divide-border">
        {cuotas.map((cuota) => (
          <li key={cuota.numero} className="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
            <span>
              Cuota {cuota.numero}
              {cuota.numero === 1 && !definitivo && (
                <span className="ml-2 text-xs text-muted-foreground">(margen 2 días)</span>
              )}
            </span>
            <span className="font-medium">
              {cuota.importe.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €
            </span>
            <span className="text-muted-foreground w-full sm:w-auto sm:text-right">
              Vencimiento:{' '}
              {new Date(cuota.fechaVencimiento + 'T12:00:00').toLocaleDateString('es-ES')}
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}
