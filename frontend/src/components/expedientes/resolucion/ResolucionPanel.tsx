import { useId, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  Archive,
  Bell,
  CalendarClock,
  CheckCircle2,
  ExternalLink,
  FileText,
  FileUp,
  Loader2,
  Scale,
  Send,
  ThumbsDown,
  ThumbsUp,
} from 'lucide-react';
import {
  api,
  openAuthenticatedDocument,
  type GestionPostResolucionResponse,
  type ResolucionResponse,
} from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface ResolucionPanelProps {
  expedienteId: string;
  numero: string;
}

export function ResolucionPanel({ expedienteId, numero }: ResolucionPanelProps) {
  const queryClient = useQueryClient();
  const { data, isLoading, error } = useQuery({
    queryKey: ['resolucion', expedienteId],
    queryFn: () => api.getResolucion(expedienteId),
    refetchInterval: 10000,
  });

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: ['resolucion', expedienteId] });
    void queryClient.invalidateQueries({ queryKey: ['expediente', expedienteId] });
    void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-16 text-muted-foreground">
        <Loader2 className="mr-2 h-5 w-5 animate-spin" />
        Cargando resolución…
      </div>
    );
  }

  if (error || !data) {
    return (
      <p className="py-8 text-center text-sm text-destructive">
        {error instanceof Error ? error.message : 'No se pudo cargar la resolución.'}
      </p>
    );
  }

  const hechas = data.resolucion?.gestiones.filter((g) => g.hecho).length ?? 0;
  const total = data.resolucion?.gestiones.length ?? 0;

  return (
    <div className="space-y-6">
      <div className="panel space-y-4 p-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div className="flex items-start gap-3">
            <div className="panel-header-icon">
              <Scale className="h-5 w-5" />
            </div>
            <div>
              <p className="section-label">Fase 4 · {numero}</p>
              <h2 className="panel-title mt-1">Resolución</h2>
              <p className="mt-1 text-sm text-muted-foreground">
                {data.tramiteNombre ??
                  'Registre el sentido de la resolución, gestiones posteriores y cierre.'}
              </p>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            {data.resolucion && (
              <Badge
                variant={data.resolucion.outcome === 'concedida' ? 'success' : 'destructive'}
                className="gap-1"
              >
                {data.resolucion.outcome === 'concedida' ? (
                  <ThumbsUp className="h-3.5 w-3.5" />
                ) : (
                  <ThumbsDown className="h-3.5 w-3.5" />
                )}
                {data.resolucion.outcomeLabel}
              </Badge>
            )}
            <Badge variant={data.estado === 'archivado' ? 'secondary' : 'info'}>
              {data.estadoLabel}
            </Badge>
          </div>
        </div>

        {data.resolucion && total > 0 && (
          <div className="space-y-2 rounded-lg border border-border bg-muted/40 px-4 py-3">
            <div className="flex items-center justify-between text-sm">
              <span className="font-medium">Gestiones completadas</span>
              <span className="text-muted-foreground">
                {hechas} / {total}
              </span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-muted">
              <div
                className="h-full rounded-full bg-primary transition-all"
                style={{ width: `${total === 0 ? 0 : Math.round((hechas / total) * 100)}%` }}
              />
            </div>
          </div>
        )}
      </div>

      {!data.resolucion ? (
        <RegistrarResolucionBlock expedienteId={expedienteId} onDone={invalidate} />
      ) : (
        <>
          <ResolucionRegistradaBlock data={data} expedienteId={expedienteId} />
          <GestionesBlock
            key={data.resolucion.id + String(data.resolucion.gestionesCompletas)}
            data={data}
            expedienteId={expedienteId}
            onDone={invalidate}
          />
          <RecordatorioBlock data={data} expedienteId={expedienteId} onDone={invalidate} />
          <ArchivarBlock data={data} expedienteId={expedienteId} onDone={invalidate} />
        </>
      )}
    </div>
  );
}

function RegistrarResolucionBlock({
  expedienteId,
  onDone,
}: {
  expedienteId: string;
  onDone: () => void;
}) {
  const [file, setFile] = useState<File | null>(null);
  const [outcome, setOutcome] = useState<'concedida' | 'denegada' | ''>('');
  const [fecha, setFecha] = useState(new Date().toISOString().slice(0, 10));
  const fileId = useId();

  const mutation = useMutation({
    mutationFn: () => {
      if (!file || !outcome) throw new Error('Adjunte la resolución e indique el resultado.');
      return api.registrarResolucion(expedienteId, {
        resolucion: file,
        outcome,
        fechaNotificacion: fecha,
      });
    },
    onSuccess: onDone,
  });

  return (
    <section className="panel overflow-hidden">
      <div className="panel-header">
        <div className="panel-header-icon">
          <FileText className="h-5 w-5" />
        </div>
        <div>
          <h3 className="panel-title text-base">Registrar resolución administrativa</h3>
          <p className="mt-0.5 text-sm text-muted-foreground">
            Adjunte el PDF, elija el resultado y guarde. El cliente recibirá un aviso inmediato.
          </p>
        </div>
      </div>

      <div className="space-y-5 p-5">
        <div className="grid gap-4 md:grid-cols-2">
          <button
            type="button"
            onClick={() => setOutcome('concedida')}
            className={cn(
              'flex min-h-[7.5rem] flex-col items-start gap-3 rounded-xl border p-4 text-left transition-colors',
              outcome === 'concedida'
                ? 'border-emerald-300 bg-emerald-50'
                : 'border-border bg-card hover:border-primary/30 hover:bg-primary/5',
            )}
          >
            <span
              className={cn(
                'flex h-10 w-10 items-center justify-center rounded-lg',
                outcome === 'concedida' ? 'bg-emerald-100 text-emerald-700' : 'bg-muted text-primary',
              )}
            >
              <ThumbsUp className="h-5 w-5" />
            </span>
            <span>
              <span className="block font-semibold">Concedida</span>
              <span className="mt-0.5 block text-sm text-muted-foreground">
                Resolución favorable. Se mostrarán gestiones (TIE, tasas, modelos…).
              </span>
            </span>
          </button>

          <button
            type="button"
            onClick={() => setOutcome('denegada')}
            className={cn(
              'flex min-h-[7.5rem] flex-col items-start gap-3 rounded-xl border p-4 text-left transition-colors',
              outcome === 'denegada'
                ? 'border-destructive/40 bg-destructive/5'
                : 'border-border bg-card hover:border-primary/30 hover:bg-primary/5',
            )}
          >
            <span
              className={cn(
                'flex h-10 w-10 items-center justify-center rounded-lg',
                outcome === 'denegada'
                  ? 'bg-destructive/10 text-destructive'
                  : 'bg-muted text-primary',
              )}
            >
              <ThumbsDown className="h-5 w-5" />
            </span>
            <span>
              <span className="block font-semibold">Denegada</span>
              <span className="mt-0.5 block text-sm text-muted-foreground">
                Resolución desfavorable. Se indicarán plazos de recurso.
              </span>
            </span>
          </button>
        </div>

        <div
          className={cn(
            'flex min-h-[140px] flex-col rounded-xl border border-dashed p-5 transition-colors',
            file ? 'border-primary/30 bg-primary/5' : 'border-border bg-muted/30',
          )}
        >
          <div className="flex items-start justify-between gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-muted">
              <FileText className="h-5 w-5 text-primary" />
            </div>
            <Badge variant={file ? 'success' : 'secondary'} className="gap-1">
              {file ? <CheckCircle2 className="h-3.5 w-3.5" /> : null}
              {file ? 'Adjunto' : 'Pendiente'}
            </Badge>
          </div>
          <h4 className="mt-3 font-semibold">PDF de la resolución</h4>
          <p className="mt-1 text-sm text-muted-foreground">
            Documento oficial notificado por la Administración.
          </p>
          {file && <p className="mt-2 truncate text-xs font-medium">{file.name}</p>}
          <div className="mt-4">
            <input
              id={fileId}
              type="file"
              accept=".pdf,application/pdf"
              className="sr-only"
              onChange={(e) => setFile(e.target.files?.[0] ?? null)}
            />
            <Button size="sm" variant={file ? 'outline' : 'default'} asChild>
              <label htmlFor={fileId} className="cursor-pointer">
                <FileUp className="mr-1.5 h-4 w-4" />
                {file ? 'Cambiar PDF' : 'Adjuntar PDF'}
              </label>
            </Button>
          </div>
        </div>

        <div className="max-w-xs space-y-2">
          <Label htmlFor="res-fecha">Fecha de notificación</Label>
          <Input
            id="res-fecha"
            type="date"
            value={fecha}
            onChange={(e) => setFecha(e.target.value)}
          />
        </div>

        {mutation.error && (
          <p className="text-sm text-destructive">
            {mutation.error instanceof Error ? mutation.error.message : 'Error'}
          </p>
        )}

        <Button
          disabled={!file || !outcome || mutation.isPending}
          onClick={() => mutation.mutate()}
        >
          {mutation.isPending ? (
            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
          ) : (
            <Send className="mr-2 h-4 w-4" />
          )}
          Guardar y avisar al cliente
        </Button>
      </div>
    </section>
  );
}

function ResolucionRegistradaBlock({
  data,
  expedienteId,
}: {
  data: ResolucionResponse;
  expedienteId: string;
}) {
  const r = data.resolucion!;
  const concedida = r.outcome === 'concedida';

  return (
    <section
      className={cn(
        'panel overflow-hidden border',
        concedida ? 'border-emerald-200' : 'border-destructive/25',
      )}
    >
      <div
        className={cn(
          'flex flex-wrap items-center justify-between gap-4 px-5 py-4',
          concedida ? 'bg-emerald-50' : 'bg-destructive/5',
        )}
      >
        <div className="flex items-start gap-3">
          <div
            className={cn(
              'flex h-12 w-12 items-center justify-center rounded-xl',
              concedida ? 'bg-emerald-100 text-emerald-700' : 'bg-destructive/10 text-destructive',
            )}
          >
            {concedida ? <ThumbsUp className="h-6 w-6" /> : <ThumbsDown className="h-6 w-6" />}
          </div>
          <div>
            <p className="section-label">Resultado</p>
            <h3 className="mt-0.5 text-lg font-semibold">Resolución {r.outcomeLabel.toLowerCase()}</h3>
            <p className="mt-1 text-sm text-muted-foreground">
              Notificada el{' '}
              {new Date(r.fechaNotificacion + 'T12:00:00').toLocaleDateString('es-ES')}
            </p>
          </div>
        </div>
        <Button
          variant="outline"
          onClick={() => void openAuthenticatedDocument(api.resolucionArchivoUrl(expedienteId))}
        >
          <FileText className="mr-1.5 h-4 w-4" />
          Ver PDF
        </Button>
      </div>
    </section>
  );
}

function GestionesBlock({
  data,
  expedienteId,
  onDone,
}: {
  data: ResolucionResponse;
  expedienteId: string;
  onDone: () => void;
}) {
  const gestiones = data.resolucion?.gestiones ?? [];
  const [local, setLocal] = useState<GestionPostResolucionResponse[]>(gestiones);
  const [savingId, setSavingId] = useState<string | null>(null);
  const archivado = data.estado === 'archivado';

  const mutation = useMutation({
    mutationFn: (next: GestionPostResolucionResponse[]) =>
      api.actualizarGestionesResolucion(
        expedienteId,
        next.map((g) => ({ id: g.id, hecho: g.hecho })),
      ),
    onSuccess: () => {
      setSavingId(null);
      onDone();
    },
    onError: () => setSavingId(null),
  });

  const toggleHecho = (idx: number, hecho: boolean) => {
    const next = [...local];
    next[idx] = { ...local[idx], hecho };
    setLocal(next);
    setSavingId(local[idx].id);
    mutation.mutate(next);
  };

  if (gestiones.length === 0) return null;

  return (
    <section className="panel overflow-hidden">
      <div className="panel-header">
        <div className="panel-header-icon">
          <CheckCircle2 className="h-5 w-5" />
        </div>
        <div className="min-w-0 flex-1">
          <h3 className="panel-title text-base">Seguimiento de gestiones del cliente</h3>
          <p className="mt-0.5 text-sm text-muted-foreground">
            Marque cada paso cuando el cliente (o el despacho) lo haya completado. El progreso se
            guarda al instante y se refleja en el portal.
          </p>
        </div>
        {mutation.isPending && (
          <Badge variant="secondary" className="gap-1">
            <Loader2 className="h-3.5 w-3.5 animate-spin" />
            Guardando…
          </Badge>
        )}
        {!mutation.isPending && mutation.isSuccess && (
          <Badge variant="success" className="gap-1">
            <CheckCircle2 className="h-3.5 w-3.5" />
            Guardado
          </Badge>
        )}
      </div>

      <ul className="divide-y divide-border">
        {local.map((g, idx) => (
          <li key={g.id}>
            <label
              className={cn(
                'flex cursor-pointer items-start gap-4 px-5 py-4 transition-colors',
                g.hecho ? 'bg-primary/5' : 'hover:bg-muted/40',
                archivado && 'cursor-default',
              )}
            >
              <span className="mt-0.5 shrink-0">
                <input
                  type="checkbox"
                  className="sr-only"
                  checked={g.hecho}
                  disabled={archivado || savingId === g.id}
                  onChange={(e) => toggleHecho(idx, e.target.checked)}
                />
                {g.hecho ? (
                  <CheckCircle2 className="h-6 w-6 text-primary" />
                ) : (
                  <span className="flex h-6 w-6 items-center justify-center rounded-full border-2 border-border text-xs font-semibold text-muted-foreground">
                    {g.orden}
                  </span>
                )}
              </span>
              <span className="min-w-0 flex-1">
                <span
                  className={cn(
                    'block text-sm font-semibold',
                    g.hecho && 'text-muted-foreground line-through',
                  )}
                >
                  {g.titulo}
                </span>
                <span className="mt-1 block text-sm text-muted-foreground">{g.descripcion}</span>
                {g.url && (
                  <a
                    href={g.url}
                    target="_blank"
                    rel="noreferrer"
                    className="link-brand mt-2 inline-flex items-center gap-1.5"
                    onClick={(e) => e.stopPropagation()}
                  >
                    <ExternalLink className="h-3.5 w-3.5" />
                    Abrir enlace oficial
                  </a>
                )}
              </span>
            </label>
          </li>
        ))}
      </ul>

      {mutation.error && (
        <p className="px-5 py-3 text-sm text-destructive">
          {mutation.error instanceof Error ? mutation.error.message : 'Error al guardar'}
        </p>
      )}
    </section>
  );
}

function RecordatorioBlock({
  data,
  expedienteId,
  onDone,
}: {
  data: ResolucionResponse;
  expedienteId: string;
  onDone: () => void;
}) {
  const [fecha, setFecha] = useState('');
  const [servicioId, setServicioId] = useState('');
  const [tramiteId, setTramiteId] = useState('');
  const archivado = data.estado === 'archivado';

  const { data: servicios = [] } = useQuery({
    queryKey: ['servicios'],
    queryFn: () => api.getServicios(),
    enabled: !data.recordatorio && !archivado && Boolean(data.resolucion),
  });

  const { data: tramites = [] } = useQuery({
    queryKey: ['tramites', servicioId],
    queryFn: () => api.getTramites({ servicioId }),
    enabled: Boolean(servicioId),
  });

  const mutation = useMutation({
    mutationFn: () =>
      api.programarRecordatorioFuturo(expedienteId, { fecha, servicioId, tramiteId }),
    onSuccess: onDone,
  });

  if (data.recordatorio) {
    return (
      <section className="panel overflow-hidden border-primary/20">
        <div className="flex flex-wrap items-start gap-4 bg-primary/5 px-5 py-5">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <Bell className="h-6 w-6" />
          </div>
          <div className="min-w-0 flex-1">
            <div className="flex flex-wrap items-center gap-2">
              <h3 className="font-semibold">Próximo trámite programado</h3>
              <Badge variant={data.recordatorio.notificado ? 'success' : 'warning'}>
                {data.recordatorio.notificado ? 'Aviso enviado' : 'Programado'}
              </Badge>
            </div>
            <p className="mt-2 flex items-center gap-2 text-sm">
              <CalendarClock className="h-4 w-4 text-primary" />
              <span className="font-medium">
                {new Date(data.recordatorio.fecha + 'T12:00:00').toLocaleDateString('es-ES', {
                  day: 'numeric',
                  month: 'long',
                  year: 'numeric',
                })}
              </span>
            </p>
            {(data.recordatorio.servicioNombre || data.recordatorio.tramiteNombre) && (
              <div className="mt-2 space-y-0.5 text-sm">
                {data.recordatorio.servicioNombre && (
                  <p>
                    <span className="text-muted-foreground">Servicio: </span>
                    {data.recordatorio.servicioNombre}
                  </p>
                )}
                {data.recordatorio.tramiteNombre && (
                  <p>
                    <span className="text-muted-foreground">Trámite: </span>
                    {data.recordatorio.tramiteNombre}
                  </p>
                )}
              </div>
            )}
            {!data.recordatorio.servicioNombre && (
              <p className="mt-1 text-sm text-muted-foreground">{data.recordatorio.motivo}</p>
            )}
          </div>
        </div>
      </section>
    );
  }

  if (archivado || !data.resolucion) return null;

  return (
    <section className="panel overflow-hidden">
      <div className="panel-header">
        <div className="panel-header-icon">
          <CalendarClock className="h-5 w-5" />
        </div>
        <div>
          <h3 className="panel-title text-base">Programar próximo trámite</h3>
          <p className="mt-0.5 text-sm text-muted-foreground">
            Opcional. Elija el servicio y el trámite futuro (p. ej. renovación). En esa fecha se
            avisará a despacho y cliente.
          </p>
        </div>
      </div>
      <div className="space-y-4 p-5">
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="rec-fecha">Fecha del recordatorio</Label>
            <Input
              id="rec-fecha"
              type="date"
              value={fecha}
              onChange={(e) => setFecha(e.target.value)}
            />
          </div>
          <div className="space-y-2 sm:col-span-2">
            <Label htmlFor="rec-servicio">Servicio</Label>
            <select
              id="rec-servicio"
              className="input-field w-full"
              value={servicioId}
              onChange={(e) => {
                setServicioId(e.target.value);
                setTramiteId('');
              }}
            >
              <option value="">Seleccionar servicio…</option>
              {servicios.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.nombre}
                </option>
              ))}
            </select>
          </div>
          <div className="space-y-2 sm:col-span-2">
            <Label htmlFor="rec-tramite">Trámite a programar</Label>
            <select
              id="rec-tramite"
              className="input-field w-full"
              value={tramiteId}
              disabled={!servicioId}
              onChange={(e) => setTramiteId(e.target.value)}
            >
              <option value="">
                {servicioId ? 'Seleccionar trámite…' : 'Primero elija un servicio'}
              </option>
              {tramites.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.nombre}
                </option>
              ))}
            </select>
          </div>
        </div>
        {mutation.error && (
          <p className="text-sm text-destructive">
            {mutation.error instanceof Error ? mutation.error.message : 'Error'}
          </p>
        )}
        <Button
          size="sm"
          variant="outline"
          disabled={!fecha || !servicioId || !tramiteId || mutation.isPending}
          onClick={() => mutation.mutate()}
        >
          {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
          Programar recordatorio
        </Button>
      </div>
    </section>
  );
}

function ArchivarBlock({
  data,
  expedienteId,
  onDone,
}: {
  data: ResolucionResponse;
  expedienteId: string;
  onDone: () => void;
}) {
  const queryClient = useQueryClient();
  const mutation = useMutation({
    mutationFn: () => api.archivarExpedienteResolucion(expedienteId),
    onSuccess: () => {
      onDone();
      void queryClient.invalidateQueries({ queryKey: ['expediente', expedienteId] });
    },
  });

  if (data.estado === 'archivado') {
    return (
      <section className="panel flex items-center gap-3 border-primary/20 bg-primary/5 p-5">
        <CheckCircle2 className="h-6 w-6 shrink-0 text-primary" />
        <div>
          <p className="font-semibold">Expediente archivado</p>
          <p className="text-sm text-muted-foreground">
            Circuito cerrado. El recordatorio programado, si existe, se enviará en su fecha.
          </p>
        </div>
      </section>
    );
  }

  if (!data.resolucion) return null;

  return (
    <section className="panel flex flex-wrap items-center justify-between gap-4 p-5">
      <div className="flex items-start gap-3">
        <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-muted">
          <Archive className="h-5 w-5 text-primary" />
        </div>
        <div>
          <h3 className="font-semibold">Archivar expediente</h3>
          <p className="mt-1 text-sm text-muted-foreground">
            {data.puedeArchivar
              ? 'Cierra el circuito normal del expediente.'
              : 'Complete las gestiones adicionales (si la resolución es concedida) antes de archivar.'}
          </p>
        </div>
      </div>
      <Button disabled={!data.puedeArchivar || mutation.isPending} onClick={() => mutation.mutate()}>
        {mutation.isPending ? (
          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
        ) : (
          <Archive className="mr-2 h-4 w-4" />
        )}
        Archivar
      </Button>
      {mutation.error && (
        <p className="w-full text-sm text-destructive">
          {mutation.error instanceof Error ? mutation.error.message : 'Error'}
        </p>
      )}
    </section>
  );
}
