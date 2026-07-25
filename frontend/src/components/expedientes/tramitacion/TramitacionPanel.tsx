import { useId, useState, type ReactNode } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  Building2,
  CheckCircle2,
  ChevronDown,
  FileText,
  FileUp,
  Loader2,
  Lock,
  Plus,
  Receipt,
  Scale,
  Send,
} from 'lucide-react';
import {
  api,
  openAuthenticatedDocument,
  type TramitacionRequerimientoResponse,
  type TramitacionResponse,
} from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface TramitacionPanelProps {
  expedienteId: string;
  numero: string;
}

export function TramitacionPanel({ expedienteId, numero }: TramitacionPanelProps) {
  const queryClient = useQueryClient();
  const { data, isLoading, error } = useQuery({
    queryKey: ['tramitacion', expedienteId],
    queryFn: () => api.getTramitacion(expedienteId),
    refetchInterval: 10000,
  });

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: ['tramitacion', expedienteId] });
    void queryClient.invalidateQueries({ queryKey: ['expediente', expedienteId] });
    void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
  };

  if (isLoading) {
    return <p className="text-muted-foreground py-8 text-center">Cargando tramitación…</p>;
  }

  if (error || !data) {
    return (
      <p className="text-destructive py-8 text-center text-sm">
        {error instanceof Error ? error.message : 'No se pudo cargar la tramitación.'}
      </p>
    );
  }

  if (!data.flujoSoportado) {
    return (
      <div className="panel p-8 text-center space-y-2">
        <Badge variant="secondary">{data.plataformaLabel}</Badge>
        <h2 className="panel-title">Flujo no configurado</h2>
        <p className="mx-auto max-w-md text-sm text-muted-foreground">
          La tramitación guiada está disponible para Mercurio. Este trámite usa {data.plataformaLabel}.
        </p>
      </div>
    );
  }

  const enDespacho = data.actorBandeja === 'despacho';
  const presentacionRegistrada = Boolean(data.presentacion);

  return (
    <div className="space-y-6">
      <div className="panel space-y-4 p-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p className="section-label">Fase 3 · {numero}</p>
            <h2 className="panel-title mt-1">{data.subfaseLabel ?? 'Tramitación'}</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              {enDespacho
                ? 'La pelota está en el despacho: complete la acción pendiente.'
                : 'La solicitud está en Mercurio. Espere o registre el seguimiento cuando exista.'}
            </p>
          </div>
          <Badge variant={enDespacho ? 'warning' : 'info'} className="gap-1.5">
            {enDespacho ? <Building2 className="h-3.5 w-3.5" /> : <Scale className="h-3.5 w-3.5" />}
            {data.actorBandejaLabel}
          </Badge>
        </div>
      </div>

      <PresentacionJustificanteSection
        data={data}
        expedienteId={expedienteId}
        onDone={invalidate}
      />

      {presentacionRegistrada ? (
        <>
          <SeguimientoBlock data={data} expedienteId={expedienteId} onDone={invalidate} />
          <RequerimientosBlock data={data} expedienteId={expedienteId} onDone={invalidate} />
        </>
      ) : (
        <LockedHint>
          Cuando adjunte y guarde presentación y justificante, podrá registrar el nº de expediente
          de extranjería y gestionar requerimientos Mercurio.
        </LockedHint>
      )}
    </div>
  );
}

function PresentacionJustificanteSection({
  data,
  expedienteId,
  onDone,
}: {
  data: TramitacionResponse;
  expedienteId: string;
  onDone: () => void;
}) {
  const [presentacion, setPresentacion] = useState<File | null>(null);
  const [justificante, setJustificante] = useState<File | null>(null);
  const [fechaJustificante, setFechaJustificante] = useState<string | null>(null);

  const ambosAdjuntos = Boolean(presentacion && justificante);
  const registrada = Boolean(data.presentacion);

  const mutation = useMutation({
    mutationFn: () => {
      if (!presentacion || !justificante || !fechaJustificante) {
        throw new Error('Adjunte presentación y justificante.');
      }
      return api.registrarPresentacionTelematica(expedienteId, {
        presentacion,
        justificante,
        fechaPresentacion: fechaJustificante,
      });
    },
    onSuccess: onDone,
  });

  const onJustificante = (file: File | null) => {
    setJustificante(file);
    if (file) {
      setFechaJustificante(new Date().toISOString().slice(0, 10));
    } else {
      setFechaJustificante(null);
    }
  };

  if (registrada && data.presentacion) {
    return (
      <div className="space-y-4">
        <div className="grid gap-4 md:grid-cols-2">
          <DocumentoCard
            title="Presentación"
            description="Documento de presentación telemática en Mercurio."
            icon={FileText}
            estado="listo"
            onVer={() =>
              void openAuthenticatedDocument(
                api.tramitacionPresentacionArchivoUrl(expedienteId, 'presentacion'),
              )
            }
          />
          <DocumentoCard
            title="Justificante"
            description="Justificante de presentación descargado de Mercurio."
            icon={Receipt}
            estado="listo"
            meta={`Fecha: ${new Date(data.presentacion.fechaPresentacion + 'T12:00:00').toLocaleDateString('es-ES')}`}
            onVer={() =>
              void openAuthenticatedDocument(
                api.tramitacionPresentacionArchivoUrl(expedienteId, 'justificante'),
              )
            }
          />
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <p className="text-sm text-muted-foreground">
        Adjunte presentación y justificante. La fecha se toma al colgar el justificante. Tras
        guardarlos podrá registrar el nº de expediente de extranjería y gestionar requerimientos.
      </p>
      <div className="grid gap-4 md:grid-cols-2">
        <DocumentoCard
          title="Presentación"
          description="PDF de la presentación telemática generada en Mercurio."
          icon={FileText}
          estado={presentacion ? 'adjunto' : 'pendiente'}
          fileName={presentacion?.name}
          onFile={setPresentacion}
        />
        <DocumentoCard
          title="Justificante"
          description="PDF del justificante. La fecha de presentación se toma al colgarlo."
          icon={Receipt}
          estado={justificante ? 'adjunto' : 'pendiente'}
          fileName={justificante?.name}
          meta={
            fechaJustificante
              ? `Fecha: ${new Date(fechaJustificante + 'T12:00:00').toLocaleDateString('es-ES')}`
              : undefined
          }
          onFile={onJustificante}
        />
      </div>

      {ambosAdjuntos ? (
        <div className="flex flex-wrap items-center gap-3">
          {mutation.error && (
            <p className="w-full text-sm text-destructive">
              {mutation.error instanceof Error ? mutation.error.message : 'Error al registrar'}
            </p>
          )}
          <Button disabled={mutation.isPending} onClick={() => mutation.mutate()}>
            {mutation.isPending ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <Send className="mr-2 h-4 w-4" />
            )}
            Guardar y avisar al cliente
          </Button>
        </div>
      ) : (
        <LockedHint>
          Adjuntando ambos documentos se desbloquea el guardado. Después podrá añadir el nº de
          expediente de extranjería (seguimiento) y los requerimientos Mercurio.
        </LockedHint>
      )}
    </div>
  );
}

function DocumentoCard({
  title,
  description,
  icon: Icon,
  estado,
  fileName,
  meta,
  onFile,
  onVer,
}: {
  title: string;
  description: string;
  icon: typeof FileText;
  estado: 'pendiente' | 'adjunto' | 'listo';
  fileName?: string;
  meta?: string;
  onFile?: (file: File | null) => void;
  onVer?: () => void;
}) {
  const inputId = useId();
  const listo = estado === 'listo' || estado === 'adjunto';

  return (
    <div
      className={cn(
        'panel flex min-h-[220px] flex-col p-5 transition-colors',
        listo ? 'border-primary/25 bg-primary/5' : 'border-dashed',
      )}
    >
      <div className="flex items-start justify-between gap-3">
        <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-muted">
          <Icon className="h-5 w-5 text-primary" />
        </div>
        <Badge variant={listo ? 'success' : 'secondary'} className="gap-1">
          {listo ? <CheckCircle2 className="h-3.5 w-3.5" /> : null}
          {estado === 'listo' ? 'Registrado' : estado === 'adjunto' ? 'Adjunto' : 'Pendiente'}
        </Badge>
      </div>
      <h3 className="mt-4 text-base font-semibold">{title}</h3>
      <p className="mt-1 flex-1 text-sm text-muted-foreground">{description}</p>
      {meta && <p className="mt-2 text-xs text-muted-foreground">{meta}</p>}
      {fileName && <p className="mt-2 truncate text-xs font-medium">{fileName}</p>}
      <div className="mt-4">
        {onVer ? (
          <Button size="sm" variant="outline" onClick={onVer}>
            Ver PDF
          </Button>
        ) : (
          <>
            <input
              id={inputId}
              type="file"
              accept=".pdf,application/pdf"
              className="sr-only"
              onChange={(e) => onFile?.(e.target.files?.[0] ?? null)}
            />
            <Button size="sm" variant={listo ? 'outline' : 'default'} asChild>
              <label htmlFor={inputId} className="cursor-pointer">
                <FileUp className="mr-1.5 h-4 w-4" />
                {listo ? 'Cambiar archivo' : 'Adjuntar PDF'}
              </label>
            </Button>
          </>
        )}
      </div>
    </div>
  );
}

function LockedHint({ children }: { children: ReactNode }) {
  return (
    <div className="flex items-start gap-3 rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
      <Lock className="mt-0.5 h-4 w-4 shrink-0" />
      <p>{children}</p>
    </div>
  );
}

function SeguimientoBlock({
  data,
  expedienteId,
  onDone,
}: {
  data: TramitacionResponse;
  expedienteId: string;
  onDone: () => void;
}) {
  const [numero, setNumero] = useState(data.presentacion?.numeroExpedienteExtranjeria ?? '');

  const mutation = useMutation({
    mutationFn: () => api.registrarSeguimientoExtranjeria(expedienteId, numero),
    onSuccess: onDone,
  });

  if (!data.presentacion) return null;

  const yaTiene = Boolean(data.presentacion.numeroExpedienteExtranjeria);

  return (
    <section className="panel space-y-4 p-5">
      <div>
        <h3 className="font-semibold">Nº expediente de extranjería (seguimiento)</h3>
        <p className="mt-1 text-sm text-muted-foreground">
          Número de 15 caracteres que asigna la oficina. Con él el cliente consulta el proceso en la
          sede electrónica (infoext2) y por SMS desde su portal.
        </p>
      </div>
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div className="flex-1 space-y-2">
          <Label htmlFor="num-expe">Nº expediente extranjería</Label>
          <Input
            id="num-expe"
            value={numero}
            onChange={(e) => setNumero(e.target.value.toUpperCase().replace(/\s+/g, ''))}
            maxLength={15}
            placeholder="15 caracteres"
            disabled={yaTiene}
          />
        </div>
        {!yaTiene && (
          <Button disabled={numero.length !== 15 || mutation.isPending} onClick={() => mutation.mutate()}>
            {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
            Guardar seguimiento
          </Button>
        )}
        {yaTiene && <Badge variant="success">Activo</Badge>}
      </div>
      {mutation.error && (
        <p className="text-sm text-destructive">
          {mutation.error instanceof Error ? mutation.error.message : 'Error'}
        </p>
      )}
    </section>
  );
}

function RequerimientosBlock({
  data,
  expedienteId,
  onDone,
}: {
  data: TramitacionResponse;
  expedienteId: string;
  onDone: () => void;
}) {
  const [abierto, setAbierto] = useState<string | null>(data.requerimientos[0]?.id ?? null);
  const [mostrarForm, setMostrarForm] = useState(false);

  return (
    <section className="panel space-y-4 p-5">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div>
          <h3 className="font-semibold">Requerimientos Mercurio</h3>
          <p className="mt-1 text-sm text-muted-foreground">
            Si Mercurio pide más documentación, añada un requerimiento para el cliente o interno del
            despacho.
          </p>
        </div>
        <Button size="sm" variant="outline" onClick={() => setMostrarForm((v) => !v)}>
          <Plus className="mr-1.5 h-4 w-4" />
          Añadir
        </Button>
      </div>

      {mostrarForm && (
        <NuevoRequerimientoForm
          expedienteId={expedienteId}
          onDone={() => {
            setMostrarForm(false);
            onDone();
          }}
          onCancel={() => setMostrarForm(false)}
        />
      )}

      {data.requerimientos.length === 0 ? (
        <p className="text-sm text-muted-foreground">No hay requerimientos registrados.</p>
      ) : (
        <ul className="space-y-2">
          {data.requerimientos.map((req) => (
            <RequerimientoItem
              key={req.id}
              req={req}
              expedienteId={expedienteId}
              open={abierto === req.id}
              onToggle={() => setAbierto((cur) => (cur === req.id ? null : req.id))}
              onDone={onDone}
            />
          ))}
        </ul>
      )}
    </section>
  );
}

function NuevoRequerimientoForm({
  expedienteId,
  onDone,
  onCancel,
}: {
  expedienteId: string;
  onDone: () => void;
  onCancel: () => void;
}) {
  const [nombre, setNombre] = useState('');
  const [descripcion, setDescripcion] = useState('');
  const [tipo, setTipo] = useState<'documento' | 'escrito'>('documento');
  const [destino, setDestino] = useState<'cliente' | 'despacho'>('despacho');

  const mutation = useMutation({
    mutationFn: () =>
      api.agregarRequerimientoMercurio(expedienteId, { nombre, descripcion, tipo, destino }),
    onSuccess: onDone,
  });

  return (
    <div className="space-y-3 rounded-lg border border-border bg-muted/30 p-4">
      <div className="grid gap-3 sm:grid-cols-2">
        <div className="space-y-2 sm:col-span-2">
          <Label>Nombre</Label>
          <Input value={nombre} onChange={(e) => setNombre(e.target.value)} placeholder="Ej. Certificado literal" />
        </div>
        <div className="space-y-2">
          <Label>Tipo</Label>
          <select
            className="input-field w-full"
            value={tipo}
            onChange={(e) => {
              const next = e.target.value as 'documento' | 'escrito';
              setTipo(next);
              if (next === 'escrito') setDestino('despacho');
            }}
          >
            <option value="documento">Documento</option>
            <option value="escrito">Escrito</option>
          </select>
        </div>
        <div className="space-y-2">
          <Label>Destino</Label>
          <select
            className="input-field w-full"
            value={destino}
            disabled={tipo === 'escrito'}
            onChange={(e) => setDestino(e.target.value as 'cliente' | 'despacho')}
          >
            <option value="despacho">Interno del despacho</option>
            {tipo === 'documento' && <option value="cliente">Para el cliente (portal)</option>}
          </select>
        </div>
        <div className="space-y-2 sm:col-span-2">
          <Label>Descripción</Label>
          <textarea
            className="input-field min-h-[4.5rem] w-full resize-y"
            value={descripcion}
            onChange={(e) => setDescripcion(e.target.value)}
            rows={2}
          />
        </div>
      </div>
      {mutation.error && (
        <p className="text-sm text-destructive">
          {mutation.error instanceof Error ? mutation.error.message : 'Error'}
        </p>
      )}
      <div className="flex gap-2">
        <Button disabled={!nombre.trim() || mutation.isPending} onClick={() => mutation.mutate()}>
          {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
          Crear requerimiento
        </Button>
        <Button variant="ghost" onClick={onCancel}>
          Cancelar
        </Button>
      </div>
    </div>
  );
}

function RequerimientoItem({
  req,
  expedienteId,
  open,
  onToggle,
  onDone,
}: {
  req: TramitacionRequerimientoResponse;
  expedienteId: string;
  open: boolean;
  onToggle: () => void;
  onDone: () => void;
}) {
  const [archivo, setArchivo] = useState<File | null>(null);
  const [justificante, setJustificante] = useState<File | null>(null);
  const [escritoId, setEscritoId] = useState('');
  const archivoId = useId();
  const justificanteId = useId();

  const cerrado = req.estado === 'cerrado' || req.estado === 'presentado';
  const esEscrito = req.tipo === 'escrito';
  const esCliente = req.destino === 'cliente';
  const puedePresentar =
    Boolean(justificante) && (req.tieneArchivo || (!esEscrito && Boolean(archivo)));

  const { data: escritos = [] } = useQuery({
    queryKey: ['escritos', expedienteId],
    queryFn: () => api.getEscritosExpediente(expedienteId),
    enabled: open && esEscrito && !cerrado,
  });

  const subirMutation = useMutation({
    mutationFn: () => {
      if (!archivo) throw new Error('Seleccione un archivo');
      return api.subirArchivoRequerimientoMercurio(expedienteId, req.id, archivo);
    },
    onSuccess: () => {
      setArchivo(null);
      onDone();
    },
  });

  const escritoMutation = useMutation({
    mutationFn: () => {
      if (!escritoId) throw new Error('Seleccione un escrito');
      return api.vincularEscritoRequerimientoMercurio(expedienteId, req.id, escritoId);
    },
    onSuccess: () => {
      setEscritoId('');
      onDone();
    },
  });

  const presentarMutation = useMutation({
    mutationFn: () => {
      if (!justificante) throw new Error('Adjunte el justificante de presentación');
      if (!req.tieneArchivo && (esEscrito || !archivo)) {
        throw new Error(
          esEscrito
            ? 'Vincule un escrito antes de presentar'
            : 'Adjunte el documento antes de presentar',
        );
      }
      return api.presentarRequerimientoMercurio(
        expedienteId,
        req.id,
        justificante,
        !req.tieneArchivo && archivo ? archivo : null,
      );
    },
    onSuccess: () => {
      setArchivo(null);
      setJustificante(null);
      onDone();
    },
  });

  return (
    <li className={cn('rounded-lg border border-border', open && 'border-primary/30 bg-primary/5')}>
      <button
        type="button"
        className="flex w-full items-start justify-between gap-3 px-4 py-3 text-left hover:bg-muted/40"
        onClick={onToggle}
        aria-expanded={open}
      >
        <div className="min-w-0">
          <p className="text-sm font-medium">{req.nombre}</p>
          <div className="mt-1.5 flex flex-wrap gap-1.5">
            <Badge variant="secondary">{req.tipoLabel}</Badge>
            <Badge variant="outline">{req.destinoLabel}</Badge>
            <Badge variant={cerrado ? 'success' : 'warning'}>{req.estadoLabel}</Badge>
          </div>
        </div>
        <ChevronDown
          className={cn(
            'mt-0.5 h-4 w-4 shrink-0 text-muted-foreground transition-transform',
            open && 'rotate-180',
          )}
          aria-hidden
        />
      </button>
      {open && !cerrado && (
        <div className="space-y-4 border-t border-border px-4 py-3">
          {req.descripcion && <p className="text-sm text-muted-foreground">{req.descripcion}</p>}

          {!esEscrito && (
            <div className="space-y-2">
              <Label htmlFor={archivoId}>
                {req.tieneArchivo
                  ? 'Documento adjunto — puede sustituirlo'
                  : esCliente
                    ? 'Adjuntar documento (si lo aporta el despacho)'
                    : 'Adjuntar documento'}
              </Label>
              {req.tieneArchivo && (
                <p className="text-sm text-muted-foreground">
                  Actual: {req.archivoNombre ?? 'documento'}
                </p>
              )}
              {esCliente && !req.tieneArchivo && (
                <p className="text-xs text-muted-foreground">
                  El cliente puede subirlo desde su portal. También puede adjuntarlo usted aquí.
                </p>
              )}
              <Input
                id={archivoId}
                type="file"
                accept=".pdf,application/pdf,image/*"
                onChange={(e) => setArchivo(e.target.files?.[0] ?? null)}
              />
              <Button
                size="sm"
                variant="outline"
                disabled={!archivo || subirMutation.isPending}
                onClick={() => subirMutation.mutate()}
              >
                {subirMutation.isPending ? (
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                ) : (
                  <FileUp className="mr-2 h-4 w-4" />
                )}
                {req.tieneArchivo ? 'Sustituir documento' : 'Adjuntar documento'}
              </Button>
            </div>
          )}

          {esEscrito && (
            <div className="space-y-2">
              <Label htmlFor={`escrito-${req.id}`}>
                {req.tieneArchivo
                  ? 'Escrito vinculado — puede elegir otro'
                  : 'Seleccionar escrito del expediente'}
              </Label>
              {req.tieneArchivo && (
                <p className="text-sm text-muted-foreground">
                  Actual: {req.archivoNombre ?? 'escrito'}
                </p>
              )}
              {escritos.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  No hay escritos en este expediente. Genérelo en la pestaña Escritos.
                </p>
              ) : (
                <select
                  id={`escrito-${req.id}`}
                  className="input-field w-full"
                  value={escritoId}
                  onChange={(e) => setEscritoId(e.target.value)}
                >
                  <option value="">Elegir escrito…</option>
                  {escritos.map((e) => (
                    <option key={e.id} value={e.id}>
                      {e.titulo}
                    </option>
                  ))}
                </select>
              )}
              <Button
                size="sm"
                variant="outline"
                disabled={!escritoId || escritoMutation.isPending}
                onClick={() => escritoMutation.mutate()}
              >
                {escritoMutation.isPending ? (
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                ) : (
                  <Send className="mr-2 h-4 w-4" />
                )}
                {req.tieneArchivo ? 'Cambiar escrito' : 'Vincular escrito'}
              </Button>
            </div>
          )}

          <div className="space-y-2 border-t border-border pt-3">
            <Label htmlFor={justificanteId}>Justificante de presentación en Mercurio</Label>
            <p className="text-xs text-muted-foreground">
              Cuando lo haya entregado en la plataforma, adjunte el justificante para cerrar el
              requerimiento.
            </p>
            <Input
              id={justificanteId}
              type="file"
              accept=".pdf,application/pdf"
              onChange={(e) => setJustificante(e.target.files?.[0] ?? null)}
            />
            <Button
              size="sm"
              disabled={!puedePresentar || presentarMutation.isPending}
              onClick={() => presentarMutation.mutate()}
            >
              {presentarMutation.isPending ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : (
                <CheckCircle2 className="mr-2 h-4 w-4" />
              )}
              Presentado — cerrar requerimiento
            </Button>
          </div>

          {(subirMutation.error || escritoMutation.error || presentarMutation.error) && (
            <p className="text-sm text-destructive">
              {(subirMutation.error || escritoMutation.error || presentarMutation.error) instanceof
              Error
                ? (subirMutation.error || escritoMutation.error || presentarMutation.error)!.message
                : 'Error'}
            </p>
          )}
        </div>
      )}
    </li>
  );
}
