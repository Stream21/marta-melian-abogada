import { CreditCard, Landmark } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { MetodoPago, PlanPago } from '@/api/client';
import type { ExpedienteAltaState } from './types';

interface PasoPagoPanelProps {
  state: ExpedienteAltaState;
  onChange: (patch: Partial<ExpedienteAltaState>) => void;
}

const METODOS: { value: MetodoPago; label: string; sub: string; icon: typeof CreditCard }[] = [
  { value: 'manual', label: 'Pago Manual', sub: 'Efectivo, transferencia', icon: Landmark },
  { value: 'digital', label: 'Pasarela Digital', sub: 'Stripe', icon: CreditCard },
];

export function PasoPagoPanel({ state, onChange }: PasoPagoPanelProps) {
  const cuotaMensual =
    state.planPago === 'fraccionado' && state.numCuotas > 0
      ? state.honorarios / state.numCuotas
      : state.honorarios;

  return (
    <div className="panel p-6">
      <div className="panel-header border-0 p-0 mb-6">
        <div className="flex w-full items-start justify-between">
          <div className="flex items-center gap-3">
            <div className="panel-header-icon">
              <CreditCard className="h-5 w-5" />
            </div>
            <div>
              <h2 className="panel-title">Condiciones Económicas</h2>
              <p className="text-sm text-muted-foreground">
                Configure el método de pago y las facilidades de financiación.
              </p>
            </div>
          </div>
          <div className="text-right">
            <p className="section-label">Total presupuesto</p>
            <p className="text-xl font-bold text-primary">
              {state.honorarios.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €
            </p>
          </div>
        </div>
      </div>

      <div className="space-y-8">
        <div>
          <p className="section-label mb-3">1. Método de pago</p>
          <div className="grid gap-3 sm:grid-cols-2">
            {METODOS.map((m) => {
              const Icon = m.icon;
              const selected = state.metodoPago === m.value;
              return (
                <button
                  key={m.value}
                  type="button"
                  onClick={() => onChange({ metodoPago: m.value })}
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
          <p className="section-label mb-3">2. Plan de financiación</p>
          <div className="mb-4 flex rounded-lg border border-border p-1 w-fit">
            {(['unico', 'fraccionado'] as PlanPago[]).map((plan) => (
              <button
                key={plan}
                type="button"
                onClick={() =>
                  onChange({
                    planPago: plan,
                    numCuotas: plan === 'unico' ? 1 : Math.max(2, state.numCuotas),
                  })
                }
                className={cn(
                  'rounded-md px-4 py-2 text-sm font-medium transition-colors',
                  state.planPago === plan
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:text-foreground',
                )}
              >
                {plan === 'unico' ? 'Pago único' : 'Pago fraccionado'}
              </button>
            ))}
          </div>

          {state.planPago === 'fraccionado' && (
            <div className="max-w-md">
              <p className="mb-2 text-sm text-muted-foreground">
                Seleccione el número de cuotas: <strong>{state.numCuotas} meses</strong>
              </p>
              <input
                type="range"
                min={1}
                max={4}
                value={state.numCuotas}
                onChange={(e) => onChange({ numCuotas: parseInt(e.target.value, 10) })}
                className="w-full accent-primary"
              />
              <div className="flex justify-between text-xs text-muted-foreground">
                <span>1 mes</span>
                <span>4 meses</span>
              </div>
            </div>
          )}

          <div className="mt-6 grid gap-3 sm:grid-cols-3">
            <div className="rounded-lg border border-border p-4">
              <p className="section-label">Precio total</p>
              <p className="mt-1 text-lg font-bold">
                {state.honorarios.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €
              </p>
            </div>
            <div className="rounded-lg border-2 border-primary bg-primary/5 p-4">
              <p className="section-label text-primary">Cuota mensual</p>
              <p className="mt-1 text-lg font-bold text-primary">
                {cuotaMensual.toLocaleString('es-ES', { minimumFractionDigits: 2 })} € / mes
              </p>
              {state.planPago === 'fraccionado' && (
                <p className="text-xs text-muted-foreground">Durante {state.numCuotas} meses</p>
              )}
            </div>
            <div className="rounded-lg border border-border p-4">
              <p className="section-label">Cargos adicionales</p>
              <p className="mt-1 text-sm font-medium text-emerald-700">Sin intereses</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
