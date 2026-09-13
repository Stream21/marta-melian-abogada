import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  ChevronRight,
  ClipboardList,
  ExternalLink,
  FileText,
  FileUp,
  Loader2,
} from 'lucide-react';
import {
  api,
  type AccesoExpedienteResponse,
  type AccesoTramitacionRequerimientoResponse,
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
  const seguimiento = tramitacion.instruccionesSeguimiento;
  const numeroExpediente =
    seguimiento?.numeroExpedienteExtranjeria ??
    tramitacion.numeroExpedienteExtranjeria ??
    null;
  const estadoBadge = (() => {
    switch (tramitacion.estadoCliente) {
      case 'accion_requerida':
        return { variant: 'warning' as const, label: 'Requiere su acción' };
      case 'en_tramite_despacho':
      case 'preparacion':
        return { variant: 'secondary' as const, label: 'Pendiente del despacho' };
      case 'pendiente_tramitacion':
        return { variant: 'secondary' as const, label: 'Pendiente de la Administración' };
      case 'en_seguimiento':
        return { variant: 'info' as const, label: 'En seguimiento' };
      default:
        return { variant: 'secondary' as const, label: tramitacion.estadoClienteLabel };
    }
  })();

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
      <section className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-card px-5 py-4 shadow-sm">
        <p className="section-label">Estado de su solicitud</p>
        <Badge variant={estadoBadge.variant} className="shrink-0 text-sm font-semibold">
          {estadoBadge.label}
        </Badge>
      </section>

      {seguimiento && (
        <section className="space-y-4 rounded-xl border border-primary/20 bg-primary/5 p-5 shadow-sm">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div className="min-w-0 space-y-1">
              <p className="section-label">Nº expediente de extranjería</p>
              {numeroExpediente ? (
                <p className="font-mono text-lg font-semibold tracking-wide text-foreground">
                  {numeroExpediente}
                </p>
              ) : (
                <p className="text-sm text-muted-foreground">
                  Pendiente de asignación por la Administración
                </p>
              )}
            </div>
            <Button asChild size="sm" className="min-h-11 w-full shrink-0 sm:w-auto">
              <a href={seguimiento.webUrl} target="_blank" rel="noreferrer">
                <ExternalLink className="mr-1.5 h-4 w-4" />
                Abrir consulta en la sede
              </a>
            </Button>
          </div>
        </section>
      )}

      {pendientes.length > 0 && (
        <section className="space-y-4">
          <h3 className="font-semibold">Pendiente por su parte</h3>
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

      {tramitacion.requerimientosCliente.length > 0 && pendientes.length === 0 && (
        <p className="text-sm text-muted-foreground">
          No tiene documentos pendientes de envío en este momento.
        </p>
      )}
    </div>
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
