import { Info, Mail, MessageCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { ExpedienteAltaState } from './types';

interface PasoFinalizarPanelProps {
  state: ExpedienteAltaState;
  onChange: (patch: Partial<ExpedienteAltaState>) => void;
}

export function PasoFinalizarPanel({ state, onChange }: PasoFinalizarPanelProps) {
  const tieneTelefono = !!state.telefono.trim();
  const tieneEmail = !!state.email.trim();

  const canalesDisponibles = [
    ...(tieneTelefono
      ? [
          {
            id: 'whatsapp' as const,
            label: 'SMS / WhatsApp',
            icon: MessageCircle,
            desc: 'Notificación instantánea con enlace de acceso.',
          },
        ]
      : []),
    ...(tieneEmail
      ? [
          {
            id: 'email' as const,
            label: 'Correo electrónico',
            icon: Mail,
            desc: 'Método tradicional con copia de la documentación legal.',
          },
        ]
      : []),
  ];

  const seleccionados =
    (state.canalesNotificacion.whatsapp && tieneTelefono ? 1 : 0) +
    (state.canalesNotificacion.email && tieneEmail ? 1 : 0);

  const toggleCanal = (id: 'whatsapp' | 'email') => {
    const actual = state.canalesNotificacion[id];
    if (actual && seleccionados <= 1) {
      return;
    }
    onChange({
      canalesNotificacion: {
        ...state.canalesNotificacion,
        [id]: !actual,
      },
    });
  };

  return (
    <div className="panel p-6">
      <div className="panel-header border-0 p-0 mb-6">
        <div>
          <h2 className="panel-title">Envío y Puesta en Marcha</h2>
          <p className="text-sm text-muted-foreground">
            Seleccione al menos un canal de comunicación para el alta del cliente.
          </p>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 mb-6">
        {canalesDisponibles.map((canal) => {
          const Icon = canal.icon;
          const seleccionado = state.canalesNotificacion[canal.id];
          const esUnicoSeleccionado = seleccionado && seleccionados === 1;

          return (
            <button
              key={canal.id}
              type="button"
              onClick={() => toggleCanal(canal.id)}
              disabled={esUnicoSeleccionado}
              className={cn(
                'flex items-start gap-4 rounded-lg border-2 p-5 text-left transition-colors',
                seleccionado
                  ? 'border-primary bg-primary/5'
                  : 'border-border bg-card hover:border-primary/30',
                esUnicoSeleccionado && 'cursor-not-allowed opacity-90',
              )}
            >
              <div
                className={cn(
                  'rounded-lg p-2',
                  seleccionado ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground',
                )}
              >
                <Icon className="h-6 w-6" />
              </div>
              <div>
                <p className="font-semibold">{canal.label}</p>
                <p className="mt-1 text-sm text-muted-foreground">{canal.desc}</p>
                <span
                  className={cn(
                    'mt-2 inline-block rounded-full px-2 py-0.5 text-xs font-medium',
                    seleccionado
                      ? 'bg-emerald-100 text-emerald-700'
                      : 'bg-muted text-muted-foreground',
                  )}
                >
                  {seleccionado ? 'Seleccionado' : 'No seleccionado'}
                </span>
                {esUnicoSeleccionado && (
                  <p className="mt-1 text-xs text-muted-foreground">
                    Debe mantener al menos un canal activo.
                  </p>
                )}
              </div>
            </button>
          );
        })}
      </div>

      {canalesDisponibles.length === 0 && (
        <p className="text-sm text-destructive mb-4" role="alert">
          Debe indicar al menos un teléfono o un email válido para notificar al cliente.
        </p>
      )}

      {seleccionados === 0 && canalesDisponibles.length > 0 && (
        <p className="text-sm text-destructive mb-4" role="alert">
          Seleccione al menos un canal de comunicación.
        </p>
      )}

      <div className="flex gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4">
        <Info className="h-5 w-5 shrink-0 text-blue-600" />
        <p className="text-sm text-blue-800">
          Al finalizar, el cliente recibirá una notificación de bienvenida con el enlace de acceso
          para iniciar la fase de contratación (firma y primer pago).
        </p>
      </div>
    </div>
  );
}
