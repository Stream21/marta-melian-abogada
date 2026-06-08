import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from '@tanstack/react-router';
import { ChevronDown, Euro, Layers, Save, Tag } from 'lucide-react';
import { api } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { PLATAFORMAS_TRAMITACION, type PlataformaTramitacionValue } from '@/lib/tramite-plataformas';
import { cn } from '@/lib/utils';

export type TramiteFormMode = 'create' | 'edit';

export interface TramiteFormProps {
  mode: TramiteFormMode;
  tramiteId?: string;
  initialServicioId?: string;
  initialNombre?: string;
  initialHonorarios?: number;
  initialPlataforma?: PlataformaTramitacionValue;
  initialRequiereProcurador?: boolean;
}

export function TramiteForm({
  mode,
  tramiteId,
  initialServicioId = '',
  initialNombre = '',
  initialHonorarios,
  initialPlataforma = 'mercurio',
  initialRequiereProcurador = false,
}: TramiteFormProps) {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [servicioId, setServicioId] = useState(initialServicioId);
  const [nombre, setNombre] = useState(initialNombre);
  const [honorarios, setHonorarios] = useState(
    initialHonorarios !== undefined ? String(initialHonorarios) : '',
  );
  const [nombreError, setNombreError] = useState(false);
  const [servicioError, setServicioError] = useState(false);
  const [honorariosError, setHonorariosError] = useState(false);
  const [plataforma, setPlataforma] = useState<PlataformaTramitacionValue>(initialPlataforma);
  const [requiereProcurador, setRequiereProcurador] = useState(initialRequiereProcurador);

  const { data: serviciosActivos = [], isLoading: loadingServicios } = useQuery({
    queryKey: ['servicios', { incluirInactivos: false }],
    queryFn: () => api.getServicios({ incluirInactivos: false }),
  });

  const { data: servicioActual } = useQuery({
    queryKey: ['servicio', initialServicioId],
    queryFn: () => api.getServicio(initialServicioId),
    enabled: mode === 'edit' && Boolean(initialServicioId),
  });

  const serviciosOptions = (() => {
    const options = [...serviciosActivos];
    if (
      mode === 'edit' &&
      servicioActual &&
      !servicioActual.activo &&
      !options.some((s) => s.id === servicioActual.id)
    ) {
      options.unshift(servicioActual);
    }
    return options;
  })();

  const parseHonorarios = (value: string): number | null => {
    const normalized = value.trim().replace(',', '.');
    if (!normalized) return null;
    const parsed = Number(normalized);
    return Number.isFinite(parsed) ? parsed : null;
  };

  type CreateNavigateTo = 'list' | 'fases';

  const createMutation = useMutation({
    mutationFn: async (navigateTo: CreateNavigateTo) => {
      const parsed = parseHonorarios(honorarios);
      if (parsed === null || parsed <= 0) throw new Error('Los honorarios deben ser mayores que cero.');
      const tramite = await api.postTramite({
        servicioId,
        nombre: nombre.trim(),
        honorarios: parsed,
        plataforma,
        requiereProcurador,
      });
      return { tramite, navigateTo };
    },
    onSuccess: ({ tramite, navigateTo }) => {
      void queryClient.invalidateQueries({ queryKey: ['tramites'] });
      if (navigateTo === 'fases') {
        navigate({
          to: '/config/tramites/$tramiteId/fases',
          params: { tramiteId: tramite.id },
        } as never);
        return;
      }
      navigate({ to: '/config/tramites' } as never);
    },
  });

  const updateMutation = useMutation({
    mutationFn: () => {
      if (!tramiteId) throw new Error('Falta el identificador.');
      const parsed = parseHonorarios(honorarios);
      if (parsed === null || parsed <= 0) throw new Error('Los honorarios deben ser mayores que cero.');
      return api.putTramite(tramiteId, {
        servicioId,
        nombre: nombre.trim(),
        honorarios: parsed,
        plataforma,
        requiereProcurador,
      });
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['tramites'] });
      void queryClient.invalidateQueries({ queryKey: ['tramite', tramiteId] });
      navigate({ to: '/config/tramites' } as never);
    },
  });

  const mutation = mode === 'create' ? createMutation : updateMutation;
  const saveError =
    mutation.error instanceof Error ? mutation.error.message : mutation.isError ? 'No se pudo guardar.' : null;

  const handleCancel = () => {
    navigate({ to: '/config/tramites' } as never);
  };

  const validateForm = (): boolean => {
    const trimmed = nombre.trim();
    const parsed = parseHonorarios(honorarios);
    let valid = true;

    if (!trimmed) {
      setNombreError(true);
      valid = false;
    } else {
      setNombreError(false);
    }

    if (!servicioId) {
      setServicioError(true);
      valid = false;
    } else {
      setServicioError(false);
    }

    if (parsed === null || parsed <= 0) {
      setHonorariosError(true);
      valid = false;
    } else {
      setHonorariosError(false);
    }

    return valid;
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!validateForm()) return;
    if (mode === 'create') {
      createMutation.mutate('list');
      return;
    }
    updateMutation.mutate();
  };

  const handleCreateAndConfigure = () => {
    if (!validateForm()) return;
    createMutation.mutate('fases');
  };

  const isPending = mutation.isPending;

  return (
    <form onSubmit={handleSubmit} className="panel">
      <div className="space-y-6 p-6">
        {saveError && (
          <p className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive" role="alert">
            {saveError}
          </p>
        )}

        <div className="space-y-2">
          <Label htmlFor="tramite-servicio" className="text-foreground">
            Servicio <span className="text-destructive">*</span>
          </Label>
          <div className="relative">
            <select
              id="tramite-servicio"
              value={servicioId}
              onChange={(e) => {
                setServicioId(e.target.value);
                if (servicioError && e.target.value) setServicioError(false);
              }}
              className={cn(
                'h-10 w-full appearance-none cursor-pointer rounded-lg border border-input bg-muted/50 px-3 pr-10 text-sm',
                'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring focus-visible:border-primary',
                'disabled:cursor-not-allowed disabled:opacity-50',
                !servicioId ? 'text-muted-foreground' : 'text-foreground',
                servicioError && 'border-destructive',
              )}
              disabled={isPending || loadingServicios}
              aria-invalid={servicioError}
            >
              <option value="">Seleccione un servicio…</option>
              {serviciosOptions.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.nombre}{!s.activo ? ' (inactivo)' : ''}
                </option>
              ))}
            </select>
            <ChevronDown
              className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
              aria-hidden
            />
          </div>
          {servicioError && (
            <p className="text-xs text-destructive" role="alert">
              Seleccione un servicio.
            </p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="tramite-nombre" className="text-foreground">
            Nombre del trámite <span className="text-destructive">*</span>
          </Label>
          <div className="relative">
            <Tag
              className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
              aria-hidden
            />
            <Input
              id="tramite-nombre"
              value={nombre}
              onChange={(e) => {
                setNombre(e.target.value);
                if (nombreError && e.target.value.trim()) setNombreError(false);
              }}
              placeholder="Ej. Despidos, Separación y divorcio..."
              className="pl-10 bg-muted/50 focus-visible:ring-ring focus-visible:border-primary"
              aria-invalid={nombreError}
              disabled={isPending}
            />
          </div>
          {nombreError && (
            <p className="text-xs text-destructive" role="alert">
              Indique un nombre para el trámite.
            </p>
          )}
        </div>

        <div className="space-y-3">
          <Label className="text-foreground">
            Plataforma <span className="text-destructive">*</span>
          </Label>
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {PLATAFORMAS_TRAMITACION.map((option) => {
              const selected = plataforma === option.value;
              return (
                <button
                  key={option.value}
                  type="button"
                  disabled={isPending}
                  onClick={() => setPlataforma(option.value)}
                  className={cn(
                    'flex flex-col items-start rounded-lg border p-4 text-left transition-colors',
                    'hover:border-primary/40 hover:bg-muted/50',
                    selected ? 'border-primary bg-primary/5 ring-1 ring-primary/30' : 'border-border bg-card',
                  )}
                  aria-pressed={selected}
                >
                  <span className="text-sm font-semibold text-foreground">{option.label}</span>
                  <span className="mt-0.5 text-xs text-muted-foreground leading-snug">{option.description}</span>
                </button>
              );
            })}
          </div>
        </div>

        <div className="flex items-start gap-3 rounded-lg border border-border bg-muted/30 px-4 py-3">
          <input
            id="tramite-procurador"
            type="checkbox"
            checked={requiereProcurador}
            onChange={(e) => setRequiereProcurador(e.target.checked)}
            disabled={isPending}
            className="mt-0.5 h-4 w-4 rounded border-input text-primary focus-visible:ring-1 focus-visible:ring-ring"
          />
          <div className="space-y-0.5">
            <Label htmlFor="tramite-procurador" className="cursor-pointer text-foreground">
              Requiere procurador
            </Label>
            <p className="text-xs text-muted-foreground">
              Marque si el trámite exige intervención de procurador (habitual en vía LexNET).
            </p>
          </div>
        </div>

        <div className="space-y-2">
          <Label htmlFor="tramite-honorarios" className="text-foreground">
            Honorarios del abogado (€) <span className="text-destructive">*</span>
          </Label>
          <div className="relative">
            <Euro
              className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
              aria-hidden
            />
            <Input
              id="tramite-honorarios"
              type="text"
              inputMode="decimal"
              value={honorarios}
              onChange={(e) => {
                setHonorarios(e.target.value);
                const parsed = parseHonorarios(e.target.value);
                if (honorariosError && parsed !== null && parsed > 0) setHonorariosError(false);
              }}
              placeholder="Ej. 850"
              className="pl-10 bg-muted/50 focus-visible:ring-ring focus-visible:border-primary"
              aria-invalid={honorariosError}
              disabled={isPending}
            />
          </div>
          <p className="text-xs text-muted-foreground">
            Importe de referencia. Podrá ajustarse al crear un expediente.
          </p>
          {honorariosError && (
            <p className="text-xs text-destructive" role="alert">
              Indique un importe mayor que cero.
            </p>
          )}
        </div>
      </div>

      <Separator />
      <div className="flex flex-wrap justify-end gap-3 px-6 py-4">
        <Button type="button" variant="outline" onClick={handleCancel} disabled={isPending}>
          Cancelar
        </Button>
        {mode === 'create' ? (
          <>
            <Button type="submit" variant="outline" disabled={isPending}>
              <Save className="h-4 w-4" />
              Guardar
            </Button>
            <Button type="button" onClick={handleCreateAndConfigure} disabled={isPending}>
              <Layers className="h-4 w-4" />
              Guardar y configurar
            </Button>
          </>
        ) : (
          <Button type="submit" disabled={isPending}>
            <Save className="h-4 w-4" />
            Guardar trámite
          </Button>
        )}
      </div>
    </form>
  );
}
