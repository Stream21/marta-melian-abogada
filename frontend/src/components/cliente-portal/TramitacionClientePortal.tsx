import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  CheckCircle2,
  ChevronRight,
  ClipboardList,
  Clock,
  ExternalLink,
  FileText,
  FileUp,
  Loader2,
  MessageSquare,
  Scale,
  Send,
} from 'lucide-react';
import {
  api,
  type AccesoExpedienteResponse,
  type AccesoTramitacionRequerimientoResponse,
  type AccesoTramitacionTimelineStep,
  type RequerimientoMercurioCampoResponse,
} from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface TramitacionClientePortalProps {
  token: string;
  data: AccesoExpedienteResponse;
}

function formatFechaCorta(iso: string | null | undefined): string | null {
  if (!iso) return null;
  try {
    const date = iso.includes('T') ? new Date(iso) : new Date(`${iso}T12:00:00`);
    return date.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    });
  } catch {
    return null;
  }
}

function estadoBadgeVariant(estadoCliente: string): 'warning' | 'info' | 'secondary' | 'success' {
  switch (estadoCliente) {
    case 'accion_requerida':
      return 'warning';
    case 'presentada':
    case 'en_seguimiento':
      return 'info';
    case 'preparacion':
    case 'en_tramite_despacho':
      return 'secondary';
    default:
      return 'secondary';
  }
}

export function TramitacionClientePortal({ token, data }: TramitacionClientePortalProps) {
  const tramitacion = data.tramitacion;
  const [formularioAbiertoId, setFormularioAbiertoId] = useState<string | null>(null);

  if (!tramitacion) {
    return (
      <p className="py-8 text-center text-sm text-muted-foreground">
        La información de tramitación aún no está disponible.
      </p>
    );
  }

  const pendientes = tramitacion.requerimientosCliente.filter(
    (r) =>
      r.puedeSubir || (r.campos?.some((c) => !c.valor?.trim()) ?? false),
  );
  const presentados = tramitacion.requerimientosCliente.filter(
    (r) => r.estado === 'presentado' || r.estado === 'cerrado',
  );
  const seguimiento = tramitacion.instruccionesSeguimiento;
  const numeroExpediente =
    seguimiento?.numeroExpedienteExtranjeria ??
    tramitacion.numeroExpedienteExtranjeria ??
    null;
  const fechaPresentacion = formatFechaCorta(tramitacion.fechaPresentacion);
  const timeline = tramitacion.timeline ?? [];

  const reqFormularioAbierto =
    formularioAbiertoId != null
      ? pendientes.find((r) => r.id === formularioAbiertoId) ?? null
      : null;

  if (reqFormularioAbierto) {
    return (
      <FormularioClienteVista
        token={token}
        req={reqFormularioAbierto}
        onBack={() => setFormularioAbiertoId(null)}
      />
    );
  }

  return (
    <div className="space-y-6">
      <section
        className={cn(
          'overflow-hidden rounded-xl border shadow-sm',
          tramitacion.estadoCliente === 'accion_requerida'
            ? 'border-amber-200 bg-amber-50/60'
            : 'border-border bg-card',
        )}
      >
        <div className="space-y-4 p-5">
          <div className="flex items-start gap-3">
            <div
              className={cn(
                'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl',
                tramitacion.estadoCliente === 'accion_requerida'
                  ? 'bg-amber-100 text-amber-800'
                  : 'bg-primary/10 text-primary',
              )}
            >
              <Scale className="h-6 w-6" />
            </div>
            <div className="min-w-0 flex-1 space-y-2">
              <div className="flex flex-wrap items-center gap-2">
                <p className="section-label">Fase 3 · Tramitación</p>
                <Badge variant={estadoBadgeVariant(tramitacion.estadoCliente)}>
                  {tramitacion.estadoClienteLabel}
                </Badge>
              </div>
              <h2 className="text-lg font-semibold text-foreground sm:text-xl">
                {tramitacion.estadoCliente === 'preparacion' && 'Estamos preparando su solicitud'}
                {tramitacion.estadoCliente === 'presentada' && 'Solicitud presentada'}
                {tramitacion.estadoCliente === 'en_seguimiento' && 'Solicitud en seguimiento'}
                {tramitacion.estadoCliente === 'accion_requerida' && 'Necesitamos su colaboración'}
                {tramitacion.estadoCliente === 'en_tramite_despacho' &&
                  'Su abogado gestiona un requerimiento'}
                {!['preparacion', 'presentada', 'en_seguimiento', 'accion_requerida', 'en_tramite_despacho'].includes(
                  tramitacion.estadoCliente,
                ) && tramitacion.estadoClienteLabel}
              </h2>
              {tramitacion.mensajeEstado && (
                <p className="text-sm text-muted-foreground">{tramitacion.mensajeEstado}</p>
              )}
              {fechaPresentacion && (
                <p className="text-xs text-muted-foreground">
                  Presentada el <span className="font-medium text-foreground">{fechaPresentacion}</span>
                </p>
              )}
            </div>
          </div>
        </div>
      </section>

      {timeline.length > 0 && (
        <section className="rounded-xl border border-border bg-card p-5 shadow-sm">
          <p className="section-label mb-4">Progreso de la tramitación</p>
          <ol className="space-y-0">
            {timeline.map((step, index) => (
              <TimelineStepRow
                key={step.id}
                step={step}
                isLast={index === timeline.length - 1}
              />
            ))}
          </ol>
        </section>
      )}

      {(tramitacion.presentacionRegistrada || numeroExpediente) && (
        <section className="space-y-4 rounded-xl border border-primary/20 bg-primary/5 p-5 shadow-sm">
          <div className="flex items-start gap-3">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <Send className="h-5 w-5" />
            </div>
            <div className="min-w-0 flex-1 space-y-1">
              <p className="section-label">Seguimiento ante la Administración</p>
              {numeroExpediente ? (
                <>
                  <p className="font-mono text-lg font-semibold tracking-wide text-foreground">
                    {numeroExpediente}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    Nº de expediente de extranjería. Consulte el estado en la sede o por SMS.
                  </p>
                </>
              ) : (
                <p className="text-sm text-muted-foreground">
                  La solicitud ya está presentada. El número de seguimiento aparecerá aquí cuando la
                  Administración lo asigne; también le avisaremos por correo.
                </p>
              )}
            </div>
          </div>

          {seguimiento && numeroExpediente && (
            <div className="space-y-3 border-t border-primary/15 pt-4">
              <Button asChild size="sm" className="min-h-11 w-full sm:w-auto">
                <a href={seguimiento.webUrl} target="_blank" rel="noreferrer">
                  <ExternalLink className="mr-1.5 h-4 w-4" />
                  Abrir consulta en la sede
                </a>
              </Button>
              {seguimiento.sms && seguimiento.smsTelefono && (
                <div className="flex items-start gap-2 rounded-lg border border-border bg-card px-3 py-3 text-sm">
                  <MessageSquare className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                  <p className="text-muted-foreground">
                    SMS gratuito: envíe{' '}
                    <span className="font-mono font-semibold text-foreground">{seguimiento.sms}</span>{' '}
                    al{' '}
                    <span className="font-semibold text-foreground">{seguimiento.smsTelefono}</span>
                  </p>
                </div>
              )}
            </div>
          )}
        </section>
      )}

      {pendientes.length > 0 && (
        <section className="space-y-4">
          <div>
            <h3 className="font-semibold">Pendiente por su parte</h3>
            <p className="mt-1 text-sm text-muted-foreground">
              Complete estos puntos para que su abogado pueda presentar el requerimiento.
            </p>
          </div>
          {pendientes.map((req) => (
            <RequerimientoClienteCard
              key={req.id}
              token={token}
              req={req}
              onAbrirFormulario={() => setFormularioAbiertoId(req.id)}
            />
          ))}
        </section>
      )}

      {presentados.length > 0 && pendientes.length === 0 && (
        <section className="space-y-3 rounded-xl border border-border bg-card p-4 shadow-sm">
          <p className="section-label">Requerimientos presentados</p>
          <ul className="space-y-2">
            {presentados.map((req) => (
              <li
                key={req.id}
                className="flex items-start justify-between gap-3 border-b border-border/60 pb-2 last:border-0 last:pb-0"
              >
                <div className="min-w-0">
                  <p className="text-sm font-medium text-foreground">{req.nombre}</p>
                  {req.fechaPresentacion && (
                    <p className="text-xs text-muted-foreground">
                      Presentado el {formatFechaCorta(req.fechaPresentacion)}
                    </p>
                  )}
                </div>
                <Badge variant="success">Presentado</Badge>
              </li>
            ))}
          </ul>
        </section>
      )}
    </div>
  );
}

function TimelineStepRow({
  step,
  isLast,
}: {
  step: AccesoTramitacionTimelineStep;
  isLast: boolean;
}) {
  const completado = step.estado === 'completado';
  const activo = step.estado === 'activo';
  const fecha = formatFechaCorta(step.fecha);

  return (
    <li className="flex gap-3">
      <div className="flex w-6 flex-col items-center">
        <div
          className={cn(
            'flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2',
            completado && 'border-emerald-500 bg-emerald-500 text-white',
            activo && 'border-primary bg-primary/10 text-primary',
            !completado && !activo && 'border-border bg-card text-muted-foreground',
          )}
        >
          {completado ? (
            <CheckCircle2 className="h-3.5 w-3.5" />
          ) : activo ? (
            <Clock className="h-3.5 w-3.5" />
          ) : (
            <span className="h-1.5 w-1.5 rounded-full bg-current" />
          )}
        </div>
        {!isLast && (
          <div
            className={cn(
              'mt-1 w-0.5 flex-1 min-h-[1.25rem]',
              completado ? 'bg-emerald-400' : 'bg-border',
            )}
          />
        )}
      </div>
      <div className={cn('min-w-0 pb-4', isLast && 'pb-0')}>
        <div className="flex flex-wrap items-center gap-2">
          <p
            className={cn(
              'text-sm font-semibold',
              activo ? 'text-foreground' : 'text-foreground/90',
              !completado && !activo && 'text-muted-foreground',
            )}
          >
            {step.label}
          </p>
          {activo && <Badge variant="info">Actual</Badge>}
          {fecha && (
            <span className="text-[11px] text-muted-foreground">{fecha}</span>
          )}
        </div>
        <p className="mt-0.5 text-xs text-muted-foreground">{step.descripcion}</p>
      </div>
    </li>
  );
}

function RequerimientoClienteCard({
  token,
  req,
  onAbrirFormulario,
}: {
  token: string;
  req: AccesoTramitacionRequerimientoResponse;
  onAbrirFormulario: () => void;
}) {
  const queryClient = useQueryClient();
  const docsCliente = (req.documentos ?? []).filter(
    (d) => d.responsable === 'cliente' && (d.estado === 'pendiente' || d.estado === 'rechazado'),
  );
  const campos = req.campos ?? [];
  const tieneMulti = (req.documentos?.length ?? 0) > 0 || campos.length > 0;
  const formularioNombre = req.formularioNombre?.trim() || 'Formulario pendiente';
  const formularioCometido =
    req.formularioCometido?.trim() || 'Complete los datos solicitados por el despacho.';
  const camposPendientes = campos.some((c) => !c.valor?.trim());

  if (!tieneMulti) {
    return <RequerimientoClienteUploadLegacy token={token} req={req} queryClient={queryClient} />;
  }

  return (
    <article className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
      <header className="space-y-2 border-b border-border px-4 py-4">
        <div className="flex flex-wrap items-start justify-between gap-2">
          <div className="min-w-0">
            <p className="font-semibold text-foreground">{req.nombre}</p>
            {req.descripcion?.trim() ? (
              <p className="mt-1 text-sm text-muted-foreground">{req.descripcion}</p>
            ) : null}
          </div>
          <Badge variant="warning">{req.estadoLabel}</Badge>
        </div>
      </header>

      {docsCliente.length > 0 && (
        <section className="space-y-3 border-b border-border p-4">
          <div>
            <p className="section-label">Documentos</p>
            <p className="mt-0.5 text-xs text-muted-foreground">
              Adjunte cada documento solicitado
            </p>
          </div>
          <ul className="space-y-3">
            {docsCliente.map((doc) => (
              <li key={doc.id}>
                <DocumentoClienteRow token={token} reqId={req.id} doc={doc} />
              </li>
            ))}
          </ul>
        </section>
      )}

      {campos.length > 0 && (
        <section className="space-y-3 bg-primary/5 p-4">
          <div>
            <p className="section-label">Formulario</p>
            <p className="mt-0.5 text-xs text-muted-foreground">
              Acceda al formulario para completarlo
            </p>
          </div>
          <button
            type="button"
            onClick={onAbrirFormulario}
            className={cn(
              'flex w-full items-start gap-3 rounded-lg border border-border bg-card p-4 text-left shadow-sm transition-colors',
              'hover:border-primary/30 hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
            )}
          >
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
              <ClipboardList className="h-5 w-5" />
            </div>
            <div className="min-w-0 flex-1 space-y-1">
              <p className="font-semibold text-foreground">{formularioNombre}</p>
              <p className="text-sm text-muted-foreground">{formularioCometido}</p>
              <Badge variant={camposPendientes ? 'warning' : 'success'} className="mt-1">
                {camposPendientes ? 'Pendiente de completar' : 'Completado'}
              </Badge>
            </div>
            <ChevronRight className="mt-1 h-5 w-5 shrink-0 text-muted-foreground" />
          </button>
        </section>
      )}
    </article>
  );
}

function FormularioClienteVista({
  token,
  req,
  onBack,
}: {
  token: string;
  req: AccesoTramitacionRequerimientoResponse;
  onBack: () => void;
}) {
  const nombre = req.formularioNombre?.trim() || 'Formulario';
  const cometido =
    req.formularioCometido?.trim() || 'Complete los datos solicitados por el despacho.';

  return (
    <div className="flex min-h-0 flex-1 flex-col gap-4">
      <div className="flex items-center gap-2">
        <Button type="button" variant="ghost" size="sm" className="min-h-11 px-2" onClick={onBack}>
          <ArrowLeft className="mr-1.5 h-4 w-4" />
          Volver
        </Button>
      </div>

      <section className="space-y-2 rounded-xl border border-primary/20 bg-primary/5 p-4 shadow-sm">
        <p className="section-label">Formulario</p>
        <h2 className="text-lg font-semibold text-foreground">{nombre}</h2>
        <p className="text-sm text-muted-foreground">{cometido}</p>
      </section>

      <section className="rounded-xl border border-border bg-card p-4 shadow-sm">
        <p className="section-label mb-3">Campos a completar</p>
        <CamposClienteForm token={token} reqId={req.id} campos={req.campos ?? []} onSaved={onBack} />
      </section>
    </div>
  );
}

function DocumentoClienteRow({
  token,
  reqId,
  doc,
}: {
  token: string;
  reqId: string;
  doc: AccesoTramitacionRequerimientoResponse['documentos'][number];
}) {
  const queryClient = useQueryClient();
  const [file, setFile] = useState<File | null>(null);

  const mutation = useMutation({
    mutationFn: () => {
      if (!file) throw new Error('Seleccione un archivo');
      return api.subirArchivoDocumentoRequerimientoMercurioPortal(token, reqId, doc.id, file);
    },
    onSuccess: () => {
      setFile(null);
      void queryClient.invalidateQueries({ queryKey: ['acceso', token] });
    },
  });

  return (
    <div className="space-y-2 rounded-lg border border-border p-3">
      <div className="flex items-start gap-2">
        <FileText className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
        <div className="min-w-0 flex-1">
          <p className="text-sm font-medium">{doc.nombre}</p>
          {doc.notaRechazo && <p className="mt-1 text-sm text-destructive">{doc.notaRechazo}</p>}
        </div>
      </div>
      <Input
        type="file"
        accept=".pdf,application/pdf,image/*"
        onChange={(e) => setFile(e.target.files?.[0] ?? null)}
      />
      <Button
        size="sm"
        className="min-h-11 w-full"
        disabled={!file || mutation.isPending}
        onClick={() => mutation.mutate()}
      >
        {mutation.isPending ? (
          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
        ) : (
          <FileUp className="mr-2 h-4 w-4" />
        )}
        Enviar
      </Button>
    </div>
  );
}

function CamposClienteForm({
  token,
  reqId,
  campos,
  onSaved,
}: {
  token: string;
  reqId: string;
  campos: RequerimientoMercurioCampoResponse[];
  onSaved?: () => void;
}) {
  const queryClient = useQueryClient();
  const [valores, setValores] = useState<Record<string, string>>(() =>
    Object.fromEntries(campos.map((c) => [c.id, c.valor ?? ''])),
  );

  const mutation = useMutation({
    mutationFn: () =>
      api.guardarCamposRequerimientoMercurioPortal(
        token,
        reqId,
        campos.map((c) => ({ id: c.id, valor: valores[c.id] ?? '' })),
      ),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['acceso', token] });
      onSaved?.();
    },
  });

  return (
    <div className="space-y-4">
      {campos.map((c) => (
        <div key={c.id} className="space-y-1.5">
          <Label>
            {c.etiqueta}
            {c.obligatorio ? ' *' : ''}
          </Label>
          {c.tipo === 'textarea' ? (
            <textarea
              className="input-field min-h-[4rem] w-full"
              value={valores[c.id] ?? ''}
              onChange={(e) => setValores((v) => ({ ...v, [c.id]: e.target.value }))}
            />
          ) : c.tipo === 'checkbox' ? (
            <label className="flex min-h-11 items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={['1', 'true', 'si', 'sí'].includes((valores[c.id] ?? '').toLowerCase())}
                onChange={(e) =>
                  setValores((v) => ({ ...v, [c.id]: e.target.checked ? '1' : '0' }))
                }
              />
              Confirmar
            </label>
          ) : c.tipo === 'select' ? (
            <select
              className="input-field min-h-11 w-full"
              value={valores[c.id] ?? ''}
              onChange={(e) => setValores((v) => ({ ...v, [c.id]: e.target.value }))}
            >
              <option value="">Seleccione…</option>
              {(c.opciones ?? []).map((opt) => (
                <option key={opt} value={opt}>
                  {opt}
                </option>
              ))}
            </select>
          ) : (
            <Input
              className="min-h-11"
              type={c.tipo === 'number' ? 'number' : c.tipo === 'date' ? 'date' : 'text'}
              value={valores[c.id] ?? ''}
              onChange={(e) => setValores((v) => ({ ...v, [c.id]: e.target.value }))}
            />
          )}
        </div>
      ))}
      <Button
        className="min-h-11 w-full"
        disabled={mutation.isPending}
        onClick={() => mutation.mutate()}
      >
        {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
        Guardar y continuar
      </Button>
    </div>
  );
}

function RequerimientoClienteUploadLegacy({
  token,
  req,
  queryClient,
}: {
  token: string;
  req: AccesoTramitacionRequerimientoResponse;
  queryClient: ReturnType<typeof useQueryClient>;
}) {
  const [file, setFile] = useState<File | null>(null);

  const mutation = useMutation({
    mutationFn: () => {
      if (!file) throw new Error('Seleccione un archivo');
      return api.subirArchivoRequerimientoMercurioPortal(token, req.id, file);
    },
    onSuccess: () => {
      setFile(null);
      void queryClient.invalidateQueries({ queryKey: ['acceso', token] });
    },
  });

  return (
    <article className="space-y-3 rounded-xl border border-border bg-card p-4 shadow-sm">
      <div>
        <p className="font-medium">{req.nombre}</p>
        {req.descripcion && (
          <p className="mt-1 text-sm text-muted-foreground">{req.descripcion}</p>
        )}
        <Badge variant="warning" className="mt-2">
          {req.estadoLabel}
        </Badge>
      </div>
      <div className="space-y-2">
        <Label htmlFor={`req-${req.id}`}>Adjuntar documento</Label>
        <Input
          id={`req-${req.id}`}
          type="file"
          accept=".pdf,application/pdf,image/*"
          onChange={(e) => setFile(e.target.files?.[0] ?? null)}
        />
      </div>
      {mutation.error && (
        <p className="text-sm text-destructive">
          {mutation.error instanceof Error ? mutation.error.message : 'Error al subir'}
        </p>
      )}
      <Button
        className="min-h-11 w-full"
        disabled={!file || mutation.isPending}
        onClick={() => mutation.mutate()}
      >
        {mutation.isPending ? (
          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
        ) : (
          <FileUp className="mr-2 h-4 w-4" />
        )}
        Enviar documento
      </Button>
    </article>
  );
}
