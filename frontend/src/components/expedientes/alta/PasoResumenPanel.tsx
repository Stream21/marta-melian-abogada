import { ClipboardList } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ExpedienteAltaState } from './types';

interface PasoResumenPanelProps {
  state: ExpedienteAltaState;
  onChange: (patch: Partial<ExpedienteAltaState>) => void;
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4 border-b border-border py-3 last:border-0">
      <span className="text-sm text-muted-foreground">{label}</span>
      <span className="text-sm font-medium text-right">{value}</span>
    </div>
  );
}

export function PasoResumenPanel({ state, onChange }: PasoResumenPanelProps) {
  const cliente =
    state.modoCliente === 'existente'
      ? state.clienteNombre
      : state.telefono + (state.email ? ` (${state.email})` : '');

  const planPagoLabel =
    state.planPago === 'fraccionado'
      ? `Fraccionado (${state.numCuotas} cuotas de ${(state.honorarios / state.numCuotas).toLocaleString('es-ES', { minimumFractionDigits: 2 })} €)`
      : 'Pago único';

  return (
    <div className="panel p-6">
      <div className="panel-header border-0 p-0 mb-6">
        <div className="panel-header-icon">
          <ClipboardList className="h-5 w-5" />
        </div>
        <div>
          <h2 className="panel-title">Resumen del Expediente</h2>
          <p className="text-sm text-muted-foreground">
            Revise los datos antes de configurar el envío al cliente.
          </p>
        </div>
      </div>

      <div className="max-w-lg">
        <Row label="Cliente" value={cliente || '—'} />
        <Row label="Servicio" value={state.servicioNombre || '—'} />
        <Row label="Trámite" value={state.tramiteNombre || '—'} />
        <Row
          label="Honorarios"
          value={`${state.honorarios.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €`}
        />
        <Row label="Método de pago" value={state.metodoPago === 'manual' ? 'Manual' : 'Digital'} />
        <Row label="Plan de pago" value={planPagoLabel} />
        <div className="py-3">
          <Label htmlFor="fechaVencimiento" className="text-sm text-muted-foreground">
            Fecha límite de la fase
          </Label>
          <p className="text-xs text-muted-foreground mt-1 mb-2">
            Por defecto, un mes desde hoy. Puede ajustarla antes de crear el expediente.
          </p>
          <Input
            id="fechaVencimiento"
            type="date"
            className="mt-2 max-w-xs"
            value={state.fechaVencimientoFase}
            onChange={(e) => onChange({ fechaVencimientoFase: e.target.value })}
          />
        </div>
      </div>
    </div>
  );
}
