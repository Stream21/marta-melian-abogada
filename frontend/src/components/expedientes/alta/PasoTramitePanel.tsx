import { useQuery } from '@tanstack/react-query';
import { FileText, Info } from 'lucide-react';
import { api } from '@/api/client';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TIPOS_SERVICIO } from '@/lib/servicio-tipos';
import type { ExpedienteAltaState } from './types';

interface PasoTramitePanelProps {
  state: ExpedienteAltaState;
  onChange: (patch: Partial<ExpedienteAltaState>) => void;
}

export function PasoTramitePanel({ state, onChange }: PasoTramitePanelProps) {
  const { data: servicios = [] } = useQuery({
    queryKey: ['servicios'],
    queryFn: () => api.getServicios(),
  });

  const serviciosFiltrados = state.areaTipo
    ? servicios.filter((s) => s.tipo === state.areaTipo && s.activo)
    : [];

  const { data: tramites = [] } = useQuery({
    queryKey: ['tramites', state.servicioId],
    queryFn: () => api.getTramites({ servicioId: state.servicioId }),
    enabled: !!state.servicioId,
  });

  const tramiteSeleccionado = tramites.find((t) => t.id === state.tramiteId);

  const handleTramiteChange = (tramiteId: string) => {
    const tramite = tramites.find((t) => t.id === tramiteId);
    if (tramite) {
      onChange({
        tramiteId,
        tramiteNombre: tramite.nombre,
        honorarios: tramite.honorarios,
      });
    }
  };

  return (
    <div className="grid gap-6 lg:grid-cols-2">
      <div className="panel p-6">
        <div className="panel-header border-0 p-0 mb-6">
          <div className="panel-header-icon">
            <FileText className="h-5 w-5" />
          </div>
          <div>
            <h2 className="panel-title">Selección de Trámite</h2>
            <p className="text-sm text-muted-foreground">
              Configure el tipo de caso y servicio específico para el expediente.
            </p>
          </div>
        </div>

        <div className="space-y-4">
          <div>
            <Label htmlFor="area">Área jurídica</Label>
            <select
              id="area"
              className="input-field mt-1 w-full"
              value={state.areaTipo}
              onChange={(e) =>
                onChange({
                  areaTipo: e.target.value as ExpedienteAltaState['areaTipo'],
                  servicioId: '',
                  servicioNombre: '',
                  tramiteId: '',
                  tramiteNombre: '',
                  honorarios: 0,
                })
              }
            >
              <option value="">Seleccione un área…</option>
              {TIPOS_SERVICIO.map((area) => (
                <option key={area.value} value={area.value}>
                  {area.shortLabel}
                </option>
              ))}
            </select>
          </div>

          <div>
            <Label htmlFor="servicio">Servicio</Label>
            <select
              id="servicio"
              className="input-field mt-1 w-full"
              value={state.servicioId}
              disabled={!state.areaTipo}
              onChange={(e) => {
                const servicio = serviciosFiltrados.find((s) => s.id === e.target.value);
                onChange({
                  servicioId: e.target.value,
                  servicioNombre: servicio?.nombre ?? '',
                  tramiteId: '',
                  tramiteNombre: '',
                  honorarios: 0,
                });
              }}
            >
              <option value="">Seleccione un servicio…</option>
              {serviciosFiltrados.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.nombre}
                </option>
              ))}
            </select>
          </div>

          <div>
            <Label htmlFor="tramite">Trámite</Label>
            <select
              id="tramite"
              className="input-field mt-1 w-full"
              value={state.tramiteId}
              disabled={!state.servicioId}
              onChange={(e) => handleTramiteChange(e.target.value)}
            >
              <option value="">Seleccione un trámite…</option>
              {tramites
                .filter((t) => t.activo)
                .map((t) => (
                  <option key={t.id} value={t.id}>
                    {t.nombre}
                  </option>
                ))}
            </select>
          </div>

          <div className="flex gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4">
            <Info className="h-5 w-5 shrink-0 text-blue-600" />
            <p className="text-sm text-blue-800">
              La selección del trámite determina automáticamente los honorarios base, la documentación
              requerida y el flujo de trabajo del expediente.
            </p>
          </div>
        </div>
      </div>

      <div className="panel bg-muted/30 p-6">
        <h3 className="section-label mb-4">Resumen del servicio seleccionado</h3>

        {tramiteSeleccionado ? (
          <div className="space-y-4">
            <div>
              <Label htmlFor="honorarios">Precio del servicio (€)</Label>
              <Input
                id="honorarios"
                type="number"
                min={0.01}
                step={0.01}
                value={state.honorarios || ''}
                onChange={(e) => onChange({ honorarios: parseFloat(e.target.value) || 0 })}
                className="mt-1"
              />
              <p className="mt-1 text-xs text-muted-foreground">
                Los honorarios base pueden modificarse manualmente para este cliente.
              </p>
            </div>

            <div>
              <p className="section-label mb-2">Trámite</p>
              <p className="text-sm font-medium">{tramiteSeleccionado.nombre}</p>
            </div>

            <div>
              <p className="section-label mb-2">Plataforma</p>
              <p className="text-sm capitalize">{tramiteSeleccionado.plataforma}</p>
            </div>
          </div>
        ) : (
          <p className="text-sm text-muted-foreground">Seleccione un trámite para ver el resumen.</p>
        )}
      </div>
    </div>
  );
}
