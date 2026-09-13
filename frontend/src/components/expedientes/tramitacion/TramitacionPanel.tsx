import {
  useEffect,
  useId,
  useRef,
  useState,
  type Dispatch,
  type ReactNode,
  type SetStateAction,
} from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  CheckCircle2,
  ChevronDown,
  CircleAlert,
  ClipboardList,
  FileText,
  FileUp,
  Loader2,
  Lock,
  Pencil,
  Plus,
  Receipt,
  ScrollText,
  Send,
  Trash2,
} from 'lucide-react';
import {
  api,
  openAuthenticatedDocument,
  type TipoCampoFormularioValue,
  type RequerimientoMercurioDocumentoResponse,
  type TramitacionRequerimientoResponse,
  type TramitacionResponse,
} from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

interface TramitacionPanelProps {
  expedienteId: string;
}

export function TramitacionPanel({ expedienteId }: TramitacionPanelProps) {
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
      <div className="panel p-6">
        <h2 className="text-xl font-semibold tracking-tight text-foreground sm:text-2xl">
          Fase 3 · Tramitación
        </h2>
        <p className="mt-1.5 text-sm text-muted-foreground">
          {enDespacho
            ? 'Complete la acción pendiente del despacho o gestione un requerimiento de la Administración.'
            : 'La solicitud está en Mercurio. Consulte el seguimiento o registre un requerimiento si procede.'}
        </p>
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

function formatFechaPresentacion(iso: string | null | undefined): string {
  if (!iso) return '—';
  try {
    const date = iso.includes('T') ? new Date(iso) : new Date(`${iso}T12:00:00`);
    return date.toLocaleDateString('es-ES');
  } catch {
    return '—';
  }
}

function ComprobantePresentacionCard({
  title,
  description,
  fecha,
  onVer,
}: {
  title: string;
  description: string;
  fecha?: string | null;
  onVer: () => void;
}) {
  return (
    <div className="flex items-start justify-between gap-3 rounded-lg border border-border bg-muted/20 px-3 py-3">
      <div className="min-w-0">
        <p className="text-sm font-medium">{title}</p>
        <p className="mt-0.5 truncate text-xs text-muted-foreground">{description}</p>
        <p className="mt-1 text-xs text-muted-foreground">
          Fecha: {formatFechaPresentacion(fecha)}
        </p>
      </div>
      <Button size="sm" variant="outline" className="shrink-0" onClick={onVer}>
        Ver / imprimir
      </Button>
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
  const guardado = data.presentacion?.numeroExpedienteExtranjeria ?? '';
  const [numero, setNumero] = useState(guardado);

  useEffect(() => {
    setNumero(guardado);
  }, [guardado]);

  const mutation = useMutation({
    mutationFn: () => api.registrarSeguimientoExtranjeria(expedienteId, numero),
    onSuccess: onDone,
  });

  if (!data.presentacion) return null;

  const hayCambios = numero !== guardado;
  const puedeGuardar = numero.length === 15 && hayCambios && !mutation.isPending;

  return (
    <section className="panel space-y-4 p-5">
      <h3 id="seguimiento-extranjeria-title" className="font-semibold">
        Nº expediente de extranjería (seguimiento)
      </h3>
      <p className="text-sm text-muted-foreground">
        Puede actualizarlo cuando cambie. Cada guardado con cambios notifica al cliente.
      </p>
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div className="flex-1">
          <Input
            id="num-expe"
            aria-labelledby="seguimiento-extranjeria-title"
            value={numero}
            onChange={(e) => setNumero(e.target.value.toUpperCase().replace(/\s+/g, ''))}
            maxLength={15}
            placeholder="15 caracteres"
          />
        </div>
        <Button disabled={!puedeGuardar} onClick={() => mutation.mutate()}>
          {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
          {guardado ? 'Actualizar seguimiento' : 'Guardar seguimiento'}
        </Button>
      </div>
      {mutation.error && (
        <p className="text-sm text-destructive">
          {mutation.error instanceof Error ? mutation.error.message : 'Error'}
        </p>
      )}
    </section>
  );
}

function requerimientoCerrado(estado: string): boolean {
  return estado === 'cerrado' || estado === 'presentado';
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
  const [nuevoOpen, setNuevoOpen] = useState(false);
  const hayRequerimientoAbierto = data.requerimientos.some((r) => !requerimientoCerrado(r.estado));

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 className="font-semibold">Requerimientos Mercurio</h3>
          <p className="mt-1 text-sm text-muted-foreground">
            Si Mercurio pide más documentación, añada un requerimiento para el cliente o interno del
            despacho.
          </p>
        </div>
        {!hayRequerimientoAbierto && (
          <Button size="sm" onClick={() => setNuevoOpen(true)}>
            <Plus className="mr-1.5 h-4 w-4" />
            Añadir requerimiento
          </Button>
        )}
      </div>

      <Dialog open={nuevoOpen} onOpenChange={setNuevoOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Nuevo requerimiento</DialogTitle>
            <DialogDescription>
              Indique un nombre y, si lo desea, instrucciones para el despacho o el cliente.
            </DialogDescription>
          </DialogHeader>
          <NuevoRequerimientoForm
            expedienteId={expedienteId}
            onDone={(id) => {
              setNuevoOpen(false);
              if (id) setAbierto(id);
              onDone();
            }}
            onCancel={() => setNuevoOpen(false)}
          />
        </DialogContent>
      </Dialog>

      <section className="panel space-y-4 overflow-visible p-5">
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
    </div>
  );
}

function claveDesdeEtiqueta(etiqueta: string): string {
  return etiqueta
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/\p{M}/gu, '')
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_|_$/g, '');
}

type CampoEntregableDraft = {
  id?: string;
  clave?: string;
  etiqueta: string;
  tipo: TipoCampoFormularioValue;
  opciones: string[];
};

function campoDraftVacio(): CampoEntregableDraft {
  return { etiqueta: '', tipo: 'text', opciones: [] };
}

function camposDesdeRequerimiento(
  campos: TramitacionRequerimientoResponse['campos'],
): CampoEntregableDraft[] {
  if (campos.length === 0) return [campoDraftVacio()];
  return campos.map((c) => ({
    id: c.id,
    clave: c.clave,
    etiqueta: c.etiqueta,
    tipo: c.tipo,
    opciones: c.opciones?.length ? [...c.opciones] : [],
  }));
}

function opcionesDeCampo(campo: CampoEntregableDraft): string[] {
  return campo.opciones.length > 0 ? campo.opciones : [''];
}

function camposDraftListos(draft: CampoEntregableDraft[]): CampoEntregableDraft[] {
  return draft.filter((c) => {
    if (!c.etiqueta.trim()) return false;
    if (c.tipo === 'select') return c.opciones.some((o) => o.trim());
    return true;
  });
}

function camposDraftToPayload(list: CampoEntregableDraft[]) {
  return list.map((c) => ({
    ...(c.id ? { id: c.id } : {}),
    clave: c.clave ?? claveDesdeEtiqueta(c.etiqueta),
    etiqueta: c.etiqueta.trim(),
    tipo: c.tipo,
    obligatorio: true,
    ...(c.tipo === 'select'
      ? { opciones: c.opciones.map((s) => s.trim()).filter(Boolean) }
      : {}),
  }));
}

function camposResponseToPayload(
  campos: TramitacionRequerimientoResponse['campos'],
) {
  return campos.map((c) => ({
    id: c.id,
    clave: c.clave,
    etiqueta: c.etiqueta,
    tipo: c.tipo,
    obligatorio: c.obligatorio,
    ...(c.tipo === 'select' && c.opciones?.length ? { opciones: [...c.opciones] } : {}),
  }));
}

function NuevoRequerimientoForm({
  expedienteId,
  onDone,
  onCancel,
}: {
  expedienteId: string;
  onDone: (id?: string) => void;
  onCancel: () => void;
}) {
  const [nombre, setNombre] = useState('');
  const [descripcion, setDescripcion] = useState('');

  const puedeCrear = Boolean(nombre.trim());

  const mutation = useMutation({
    mutationFn: () =>
      api.agregarRequerimientoMercurio(expedienteId, {
        nombre: nombre.trim(),
        descripcion: descripcion.trim() || undefined,
        tipo: 'documento',
        destino: 'despacho',
      }),
    onSuccess: (data) => onDone(data.id),
  });

  return (
    <div className="space-y-3">
      <div className="space-y-3">
        <div className="space-y-2">
          <Label>Nombre</Label>
          <Input
            value={nombre}
            onChange={(e) => setNombre(e.target.value)}
            placeholder="Ej. Certificado literal de nacimiento"
          />
        </div>
        <div className="space-y-2">
          <Label>Descripción (opcional)</Label>
          <textarea
            className="input-field min-h-[4.5rem] w-full resize-y"
            value={descripcion}
            onChange={(e) => setDescripcion(e.target.value)}
            rows={2}
            placeholder="Instrucciones para el despacho o el cliente"
          />
        </div>
      </div>

      {mutation.error && (
        <p className="text-sm text-destructive">
          {mutation.error instanceof Error ? mutation.error.message : 'Error'}
        </p>
      )}
      <div className="flex gap-2">
        <Button disabled={!puedeCrear || mutation.isPending} onClick={() => mutation.mutate()}>
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

function DocumentoModal({
  open,
  onOpenChange,
  expedienteId,
  reqId,
  onSuccess,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  expedienteId: string;
  reqId: string;
  onSuccess: () => void;
}) {
  const [nombre, setNombre] = useState('');
  const [responsable, setResponsable] = useState<'cliente' | 'abogado'>('cliente');
  const [maxArchivos, setMaxArchivos] = useState(1);

  useEffect(() => {
    if (!open) {
      setNombre('');
      setResponsable('cliente');
      setMaxArchivos(1);
    }
  }, [open]);

  const mutation = useMutation({
    mutationFn: () =>
      api.agregarDocumentoRequerimientoMercurio(expedienteId, reqId, {
        nombre: nombre.trim(),
        responsable,
        obligatorio: true,
        numeroArchivos: maxArchivos,
      }),
    onSuccess: () => {
      onOpenChange(false);
      onSuccess();
    },
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="flex max-w-md flex-col gap-0 overflow-hidden p-0 sm:max-w-lg">
        <DialogHeader className="border-b border-border bg-muted/30 px-6 py-4 pr-12 text-left">
          <DialogTitle>Añadir documento</DialogTitle>
          <DialogDescription>
            Defina qué documento debe aportarse y quién es responsable.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4 px-6 py-4">
          <div className="space-y-1.5">
            <Label htmlFor="doc-modal-nombre">Nombre</Label>
            <Input
              id="doc-modal-nombre"
              value={nombre}
              placeholder="Ej. Certificado de empadronamiento"
              onChange={(e) => setNombre(e.target.value)}
            />
          </div>
          <div className="space-y-1.5">
            <Label>Para</Label>
            <div className="grid grid-cols-2 gap-1 rounded-md bg-muted p-1">
              {(
                [
                  ['cliente', 'Cliente'],
                  ['abogado', 'Abogado'],
                ] as const
              ).map(([value, label]) => (
                <button
                  key={value}
                  type="button"
                  className={cn(
                    'rounded-sm px-3 py-1.5 text-sm font-medium transition-colors',
                    responsable === value
                      ? 'bg-card text-foreground shadow-sm'
                      : 'text-muted-foreground hover:text-foreground',
                  )}
                  onClick={() => setResponsable(value)}
                >
                  {label}
                </button>
              ))}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="doc-modal-num-arch">Máximo de ficheros</Label>
            <Input
              id="doc-modal-num-arch"
              type="number"
              min={1}
              max={20}
              value={maxArchivos}
              onChange={(e) => {
                const n = Number.parseInt(e.target.value, 10);
                if (Number.isNaN(n)) return;
                setMaxArchivos(Math.min(20, Math.max(1, n)));
              }}
            />
            <p className="text-xs text-muted-foreground">
              Límite de ficheros que se pueden adjuntar a este documento (no crea entregas
              adicionales).
            </p>
          </div>
          {mutation.error && (
            <p className="text-sm text-destructive">
              {mutation.error instanceof Error ? mutation.error.message : 'Error'}
            </p>
          )}
        </div>
        <DialogFooter className="border-t border-border bg-muted/20 px-6 py-4 sm:justify-end">
          <Button variant="ghost" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button disabled={!nombre.trim() || mutation.isPending} onClick={() => mutation.mutate()}>
            {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
            Añadir
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function CamposFormularioEditor({
  camposDraft,
  setCamposDraft,
}: {
  camposDraft: CampoEntregableDraft[];
  setCamposDraft: Dispatch<SetStateAction<CampoEntregableDraft[]>>;
}) {
  return (
    <div className="space-y-3">
      {camposDraft.map((campo, i) => (
        <div
          key={campo.id ?? `draft-${i}`}
          className="rounded-lg border border-border bg-muted/40 p-4 shadow-sm"
        >
          <div className="mb-3 flex items-center justify-between gap-2">
            <span className="section-label">Campo {i + 1}</span>
            {camposDraft.length > 1 && (
              <Button
                type="button"
                size="sm"
                variant="ghost"
                className="h-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                onClick={() => setCamposDraft((list) => list.filter((_, j) => j !== i))}
              >
                <Trash2 className="mr-1.5 h-3.5 w-3.5" />
                Quitar
              </Button>
            )}
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5 sm:col-span-2">
              <Label>Etiqueta</Label>
              <Input
                value={campo.etiqueta}
                placeholder="Ej. Número de expediente"
                onChange={(e) =>
                  setCamposDraft((list) =>
                    list.map((item, j) => (j === i ? { ...item, etiqueta: e.target.value } : item)),
                  )
                }
              />
            </div>
            <div className="space-y-1.5 sm:col-span-2">
              <Label>Tipo de respuesta</Label>
              <select
                className="input-field w-full"
                value={campo.tipo}
                onChange={(e) => {
                  const tipo = e.target.value as TipoCampoFormularioValue;
                  setCamposDraft((list) =>
                    list.map((item, j) =>
                      j === i
                        ? {
                            ...item,
                            tipo,
                            opciones:
                              tipo === 'select'
                                ? item.opciones.length
                                  ? item.opciones
                                  : ['']
                                : [],
                          }
                        : item,
                    ),
                  );
                }}
              >
                <option value="text">Texto</option>
                <option value="textarea">Texto largo</option>
                <option value="number">Número</option>
                <option value="date">Fecha</option>
                <option value="select">Selección</option>
                <option value="checkbox">Casilla</option>
              </select>
            </div>
          </div>
          {campo.tipo === 'select' && (
            <div className="mt-3 space-y-2 rounded-md border border-border bg-card p-3">
              <Label>Opciones de selección</Label>
              <div className="space-y-2">
                {opcionesDeCampo(campo).map((opt, oi) => (
                  <div key={oi} className="flex items-center gap-2">
                    <span className="w-5 shrink-0 text-center text-xs text-muted-foreground">
                      {oi + 1}
                    </span>
                    <Input
                      value={opt}
                      placeholder={`Opción ${oi + 1}`}
                      onChange={(e) => {
                        const next = [...opcionesDeCampo(campo)];
                        next[oi] = e.target.value;
                        setCamposDraft((list) =>
                          list.map((item, j) => (j === i ? { ...item, opciones: next } : item)),
                        );
                      }}
                    />
                  </div>
                ))}
              </div>
              <Button
                type="button"
                size="sm"
                variant="outline"
                onClick={() =>
                  setCamposDraft((list) =>
                    list.map((item, j) =>
                      j === i ? { ...item, opciones: [...opcionesDeCampo(campo), ''] } : item,
                    ),
                  )
                }
              >
                <Plus className="mr-1 h-3 w-3" /> Añadir opción
              </Button>
            </div>
          )}
        </div>
      ))}
      <Button
        type="button"
        size="sm"
        variant="outline"
        className="w-full border-dashed"
        onClick={() => setCamposDraft((c) => [...c, campoDraftVacio()])}
      >
        <Plus className="mr-1.5 h-3.5 w-3.5" /> Otro campo
      </Button>
    </div>
  );
}

function FormularioModal({
  open,
  onOpenChange,
  mode,
  expedienteId,
  req,
  onSuccess,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  mode: 'create' | 'edit';
  expedienteId: string;
  req: TramitacionRequerimientoResponse;
  onSuccess: () => void;
}) {
  const [nombre, setNombre] = useState('');
  const [cometido, setCometido] = useState('');
  const [camposDraft, setCamposDraft] = useState<CampoEntregableDraft[]>([campoDraftVacio()]);

  useEffect(() => {
    if (open) {
      setNombre(req.formularioNombre?.trim() || '');
      setCometido(req.formularioCometido?.trim() || '');
      setCamposDraft(
        mode === 'edit' ? camposDesdeRequerimiento(req.campos ?? []) : [campoDraftVacio()],
      );
    }
  }, [open, mode, req.campos, req.formularioNombre, req.formularioCometido]);

  const listos = camposDraftListos(camposDraft);
  const puedeGuardar = Boolean(nombre.trim()) && listos.length > 0;

  const mutation = useMutation({
    mutationFn: () =>
      api.gestionarCamposRequerimientoMercurio(
        expedienteId,
        req.id,
        camposDraftToPayload(listos),
        {
          formularioNombre: nombre.trim(),
          formularioCometido: cometido.trim() || null,
        },
      ),
    onSuccess: () => {
      onOpenChange(false);
      onSuccess();
    },
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="flex max-h-[85vh] max-w-lg flex-col gap-0 overflow-hidden p-0 sm:max-w-xl">
        <DialogHeader className="border-b border-border bg-muted/30 px-6 py-4 pr-12 text-left">
          <DialogTitle>{mode === 'edit' ? 'Gestionar formulario' : 'Añadir formulario'}</DialogTitle>
          <DialogDescription>
            Primero el nombre y el cometido del formulario; después defina los campos.
          </DialogDescription>
        </DialogHeader>
        <div className="flex-1 space-y-5 overflow-y-auto px-6 py-4">
          <div className="space-y-3 rounded-lg border border-border bg-card p-4">
            <div className="space-y-2">
              <Label htmlFor="formulario-nombre">Nombre del formulario</Label>
              <Input
                id="formulario-nombre"
                value={nombre}
                onChange={(e) => setNombre(e.target.value)}
                placeholder="Ej. Datos adicionales del requerimiento"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="formulario-cometido">Cometido</Label>
              <textarea
                id="formulario-cometido"
                className="input-field min-h-[4.5rem] w-full"
                value={cometido}
                onChange={(e) => setCometido(e.target.value)}
                placeholder="Para qué sirve este formulario y qué debe aportar el cliente"
              />
            </div>
          </div>
          <div className="space-y-2">
            <p className="section-label">Definición de campos</p>
            <CamposFormularioEditor camposDraft={camposDraft} setCamposDraft={setCamposDraft} />
          </div>
          {mutation.error && (
            <p className="text-sm text-destructive">
              {mutation.error instanceof Error ? mutation.error.message : 'Error'}
            </p>
          )}
        </div>
        <DialogFooter className="border-t border-border bg-muted/20 px-6 py-4 sm:justify-end">
          <Button variant="ghost" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button
            disabled={!puedeGuardar || mutation.isPending}
            onClick={() => mutation.mutate()}
          >
            {mutation.isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
            Guardar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function EscritoModal({
  open,
  onOpenChange,
  expedienteId,
  req,
  escritos,
  onSuccess,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  expedienteId: string;
  req: TramitacionRequerimientoResponse;
  escritos: Array<{ id: string; titulo: string }>;
  onSuccess: () => void;
}) {
  const [escritoId, setEscritoId] = useState('');

  useEffect(() => {
    if (!open) setEscritoId('');
  }, [open]);

  const mutation = useMutation({
    mutationFn: () => {
      if (!escritoId) throw new Error('Seleccione un escrito');
      return api.vincularEscritoRequerimientoMercurio(expedienteId, req.id, escritoId);
    },
    onSuccess: () => {
      onOpenChange(false);
      onSuccess();
    },
  });

  const escritoSelectId = useId();

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Vincular escrito</DialogTitle>
          <DialogDescription>Elija un escrito del expediente para este requerimiento.</DialogDescription>
        </DialogHeader>
        <div className="space-y-3 py-2">
          {req.tieneArchivo && req.tipo === 'escrito' && (
            <p className="text-sm text-muted-foreground">
              Vinculado: {req.archivoNombre ?? 'escrito'}
            </p>
          )}
          {escritos.length === 0 ? (
            <p className="text-sm text-muted-foreground">Cree un escrito en la pestaña Escritos.</p>
          ) : (
            <>
              <Label htmlFor={escritoSelectId}>Escrito del expediente</Label>
              <select
                id={escritoSelectId}
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
            </>
          )}
          {mutation.error && (
            <p className="text-sm text-destructive">
              {mutation.error instanceof Error ? mutation.error.message : 'Error'}
            </p>
          )}
        </div>
        <DialogFooter>
          <Button variant="ghost" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button
            disabled={!escritoId || mutation.isPending || escritos.length === 0}
            onClick={() => mutation.mutate()}
          >
            {mutation.isPending ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <Send className="mr-2 h-4 w-4" />
            )}
            Vincular
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function DevolucionMotivoModal({
  open,
  onOpenChange,
  titulo,
  onConfirm,
  pending,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  titulo: string;
  onConfirm: (nota: string) => void;
  pending?: boolean;
}) {
  const [nota, setNota] = useState('');

  useEffect(() => {
    if (!open) setNota('');
  }, [open]);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>Devolver: {titulo}</DialogTitle>
          <DialogDescription>
            Indique el motivo para el cliente. Verá este mensaje en su portal y podrá corregirlo.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-3 py-2">
          <div className="space-y-2 rounded-lg border border-amber-200 bg-amber-50/50 p-4">
            <Label htmlFor="nota-devolucion-tramitacion">Nota para el cliente</Label>
            <textarea
              id="nota-devolucion-tramitacion"
              className="input-field min-h-[100px] w-full resize-y"
              placeholder="Indique qué debe corregir o volver a subir el cliente…"
              value={nota}
              onChange={(e) => setNota(e.target.value)}
              maxLength={1000}
            />
            <p className="text-xs text-muted-foreground">Mínimo 5 caracteres.</p>
          </div>
        </div>
        <DialogFooter>
          <Button variant="ghost" onClick={() => onOpenChange(false)} disabled={pending}>
            Cancelar
          </Button>
          <Button
            variant="destructive"
            disabled={pending || nota.trim().length < 5}
            onClick={() => onConfirm(nota.trim())}
          >
            {pending ? 'Enviando…' : 'Enviar nota y devolver'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function DocumentoEntregableRow({
  doc,
  expedienteId,
  reqId,
  onDone,
  soloLectura = false,
}: {
  doc: RequerimientoMercurioDocumentoResponse;
  expedienteId: string;
  reqId: string;
  onDone: () => void;
  soloLectura?: boolean;
}) {
  const [file, setFile] = useState<File | null>(null);
  const [devolverOpen, setDevolverOpen] = useState(false);

  const toggleResponsableMutation = useMutation({
    mutationFn: () =>
      api.actualizarDocumentoRequerimientoMercurio(expedienteId, reqId, doc.id, {
        responsable: doc.responsable === 'cliente' ? 'abogado' : 'cliente',
      }),
    onSuccess: onDone,
  });

  const eliminarMutation = useMutation({
    mutationFn: () => api.eliminarDocumentoRequerimientoMercurio(expedienteId, reqId, doc.id),
    onSuccess: onDone,
  });

  const subirMutation = useMutation({
    mutationFn: () => {
      if (!file) throw new Error('Seleccione un archivo');
      return api.subirArchivoDocumentoRequerimientoMercurio(expedienteId, reqId, doc.id, file);
    },
    onSuccess: () => {
      setFile(null);
      onDone();
    },
  });

  const validarMutation = useMutation({
    mutationFn: (body: { accion: 'validar' | 'rechazar'; notaRechazo?: string }) =>
      api.validarDocumentoRequerimientoMercurio(expedienteId, reqId, doc.id, body),
    onSuccess: () => {
      setDevolverOpen(false);
      onDone();
    },
  });

  const handleEliminar = () => {
    if (window.confirm(`¿Eliminar el documento «${doc.nombre}»?`)) {
      eliminarMutation.mutate();
    }
  };

  const pendienteCliente =
    doc.responsable === 'cliente' && (doc.estado === 'pendiente' || doc.estado === 'rechazado');
  const validado = doc.estado === 'validado';
  const entregado = doc.estado === 'entregado';
  const rechazado = doc.estado === 'rechazado';

  return (
    <li
      className={cn(
        'rounded-xl border p-3 text-sm transition-shadow',
        validado && 'border-emerald-200 bg-emerald-50/40',
        entregado && 'border-amber-200 bg-amber-50/30',
        rechazado && 'border-red-200 bg-red-50/30',
        !validado && !entregado && !rechazado && 'border-border',
      )}
    >
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div className="min-w-0 flex-1 space-y-1.5">
          <div className="flex flex-wrap items-center gap-2">
            <FileText className="h-4 w-4 shrink-0 text-muted-foreground" />
            <span className="font-medium">{doc.nombre}</span>
            <Badge variant="outline">{doc.responsableLabel}</Badge>
            <Badge
              variant={
                validado ? 'success' : entregado ? 'warning' : rechazado ? 'destructive' : 'secondary'
              }
            >
              {doc.estadoLabel}
            </Badge>
            {(doc.maxArchivos ?? 1) > 1 && (
              <Badge variant="outline">Máx. {doc.maxArchivos} ficheros</Badge>
            )}
          </div>
          {doc.notaRechazo && (
            <p className="rounded border border-red-100 bg-red-50 p-2 text-xs text-red-700">
              Devuelto: {doc.notaRechazo}
            </p>
          )}
          {pendienteCliente && (
            <p className="text-xs text-muted-foreground">Pendiente del cliente</p>
          )}
          {entregado && (
            <p className="text-xs text-amber-800">
              Entregado por el cliente. Valídelo o devuélvalo con una nota.
            </p>
          )}
          {validado && (
            <div className="flex gap-2 rounded-lg border border-emerald-200 bg-emerald-50/80 p-2.5 text-xs text-emerald-900">
              <CheckCircle2 className="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600" />
              Documento validado y listo.
            </div>
          )}
        </div>
        {!soloLectura && (
          <div className="flex flex-wrap gap-1.5">
            <Button
              type="button"
              size="sm"
              variant="outline"
              disabled={toggleResponsableMutation.isPending}
              onClick={() => toggleResponsableMutation.mutate()}
            >
              {doc.responsable === 'cliente' ? 'Pasar a abogado' : 'Pasar a cliente'}
            </Button>
            <Button
              type="button"
              size="sm"
              variant="ghost"
              className="text-destructive hover:text-destructive"
              disabled={eliminarMutation.isPending}
              onClick={handleEliminar}
            >
              {eliminarMutation.isPending ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                <Trash2 className="h-4 w-4" />
              )}
            </Button>
          </div>
        )}
      </div>
      {!soloLectura &&
        doc.responsable === 'abogado' &&
        (doc.estado === 'pendiente' || doc.estado === 'rechazado') && (
          <div className="mt-2 flex flex-wrap items-end gap-2">
            <Input
              type="file"
              accept=".pdf,application/pdf,image/*"
              onChange={(e) => setFile(e.target.files?.[0] ?? null)}
            />
            <Button
              size="sm"
              variant="outline"
              disabled={!file || subirMutation.isPending}
              onClick={() => subirMutation.mutate()}
            >
              {subirMutation.isPending ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : (
                <FileUp className="mr-2 h-4 w-4" />
              )}
              Subir
            </Button>
          </div>
        )}
      {!soloLectura && doc.estado === 'entregado' && (
        <div className="mt-3 flex flex-wrap gap-2">
          <Button
            size="sm"
            disabled={validarMutation.isPending}
            onClick={() => validarMutation.mutate({ accion: 'validar' })}
          >
            {validarMutation.isPending ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <CheckCircle2 className="mr-2 h-4 w-4" />
            )}
            Validar
          </Button>
          <Button
            size="sm"
            variant="outline"
            className="border-amber-300 text-amber-800 hover:bg-amber-50"
            disabled={validarMutation.isPending}
            onClick={() => setDevolverOpen(true)}
          >
            Devolver al cliente
          </Button>
        </div>
      )}
      <DevolucionMotivoModal
        open={devolverOpen}
        onOpenChange={setDevolverOpen}
        titulo={doc.nombre}
        pending={validarMutation.isPending}
        onConfirm={(nota) =>
          validarMutation.mutate({ accion: 'rechazar', notaRechazo: nota })
        }
      />
    </li>
  );
}

function EntregablesSection({
  req,
  expedienteId,
  onDone,
  soloLectura = false,
}: {
  req: TramitacionRequerimientoResponse;
  expedienteId: string;
  onDone: () => void;
  soloLectura?: boolean;
}) {
  const [menuOpen, setMenuOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);
  const [docModalOpen, setDocModalOpen] = useState(false);
  const [formModalOpen, setFormModalOpen] = useState(false);
  const [formModalMode, setFormModalMode] = useState<'create' | 'edit'>('create');
  const [escritoModalOpen, setEscritoModalOpen] = useState(false);

  const docs = req.documentos ?? [];
  const campos = req.campos ?? [];
  const formularioCompleto =
    campos.length > 0 && campos.every((c) => Boolean(c.valor?.trim()));

  const { data: escritos = [] } = useQuery({
    queryKey: ['escritos', expedienteId],
    queryFn: () => api.getEscritosExpediente(expedienteId),
    enabled: escritoModalOpen,
  });

  const eliminarDocumentosMutation = useMutation({
    mutationFn: async () => {
      for (const doc of docs) {
        await api.eliminarDocumentoRequerimientoMercurio(expedienteId, req.id, doc.id);
      }
    },
    onSuccess: onDone,
  });

  const eliminarFormularioMutation = useMutation({
    mutationFn: () =>
      api.gestionarCamposRequerimientoMercurio(expedienteId, req.id, [], {
        formularioNombre: null,
        formularioCometido: null,
      }),
    onSuccess: onDone,
  });

  const eliminarCampoMutation = useMutation({
    mutationFn: (campoId: string) => {
      const restantes = camposResponseToPayload(campos.filter((c) => c.id !== campoId));
      return api.gestionarCamposRequerimientoMercurio(expedienteId, req.id, restantes);
    },
    onSuccess: onDone,
  });

  useEffect(() => {
    if (!menuOpen) return;
    const onPointer = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setMenuOpen(false);
      }
    };
    document.addEventListener('mousedown', onPointer);
    return () => document.removeEventListener('mousedown', onPointer);
  }, [menuOpen]);

  const openFormCreate = () => {
    setFormModalMode(campos.length > 0 ? 'edit' : 'create');
    setFormModalOpen(true);
    setMenuOpen(false);
  };

  const openFormEdit = () => {
    setFormModalMode('edit');
    setFormModalOpen(true);
  };

  const handleEliminarDocumentos = () => {
    if (
      window.confirm(
        docs.length === 1
          ? '¿Eliminar el documento de este requerimiento?'
          : `¿Eliminar los ${docs.length} documentos de este requerimiento?`,
      )
    ) {
      eliminarDocumentosMutation.mutate();
    }
  };

  const handleEliminarFormulario = () => {
    if (window.confirm('¿Eliminar el formulario completo de este requerimiento?')) {
      eliminarFormularioMutation.mutate();
    }
  };

  const handleEliminarCampo = (campoId: string, etiqueta: string) => {
    if (window.confirm(`¿Eliminar el campo «${etiqueta}»?`)) {
      eliminarCampoMutation.mutate(campoId);
    }
  };

  return (
    <>
      <div className="rounded-lg border border-border">
        <div className="relative z-10 flex flex-wrap items-start justify-between gap-2 border-b border-border bg-card px-3 py-3">
          <div>
            <p className="text-sm font-medium">
              {soloLectura ? 'Resumen de entregables' : 'Qué debe aportarse'}
            </p>
            <p className="mt-0.5 text-xs text-muted-foreground">
              {soloLectura
                ? 'Documentos y formulario gestionados en este requerimiento.'
                : 'Documentos, datos del cliente o un escrito antes de presentar.'}
            </p>
          </div>
          {!soloLectura && (
            <div className="relative" ref={menuRef}>
              <Button
                type="button"
                size="sm"
                variant="outline"
                onClick={() => setMenuOpen((v) => !v)}
                aria-expanded={menuOpen}
              >
                Añadir
                <ChevronDown className="ml-1.5 h-3.5 w-3.5" />
              </Button>
              {menuOpen && (
                <div className="absolute right-0 z-50 mt-1 min-w-[11rem] rounded-md border border-border bg-popover p-1 shadow-md">
                  <button
                    type="button"
                    className="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-muted"
                    onClick={() => {
                      setDocModalOpen(true);
                      setMenuOpen(false);
                    }}
                  >
                    <FileText className="h-3.5 w-3.5" />
                    Documento
                  </button>
                  <button
                    type="button"
                    className="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-muted"
                    onClick={openFormCreate}
                  >
                    <ClipboardList className="h-3.5 w-3.5" />
                    Formulario
                  </button>
                  <button
                    type="button"
                    className="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-muted"
                    onClick={() => {
                      setEscritoModalOpen(true);
                      setMenuOpen(false);
                    }}
                  >
                    <ScrollText className="h-3.5 w-3.5" />
                    Escrito
                  </button>
                </div>
              )}
            </div>
          )}
        </div>

        {docs.length > 0 && (
          <div className="space-y-3 bg-card p-3">
            <div className="flex items-center justify-between gap-2">
              <p className="section-label">Documentos</p>
              {!soloLectura && (
                <Button
                  type="button"
                  size="sm"
                  variant="ghost"
                  className="h-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                  disabled={eliminarDocumentosMutation.isPending}
                  onClick={handleEliminarDocumentos}
                >
                  {eliminarDocumentosMutation.isPending ? (
                    <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                  ) : (
                    <Trash2 className="mr-1.5 h-3.5 w-3.5" />
                  )}
                  Eliminar sección
                </Button>
              )}
            </div>
            <ul className="space-y-2">
              {docs.map((doc) => (
                <DocumentoEntregableRow
                  key={doc.id}
                  doc={doc}
                  expedienteId={expedienteId}
                  reqId={req.id}
                  onDone={onDone}
                  soloLectura={soloLectura}
                />
              ))}
            </ul>
          </div>
        )}

        {campos.length > 0 && (
          <div
            className={cn(
              'space-y-3 border-border p-3',
              formularioCompleto ? 'bg-emerald-50/40' : 'bg-primary/5',
              docs.length > 0 && 'border-t',
            )}
          >
            <div className="flex flex-wrap items-start justify-between gap-2">
              <div className="min-w-0">
                <p className="section-label">Formulario</p>
                <p className="mt-1 text-sm font-semibold text-foreground">
                  {req.formularioNombre?.trim() || 'Formulario sin nombre'}
                </p>
                {req.formularioCometido?.trim() ? (
                  <p className="mt-0.5 text-xs text-muted-foreground">{req.formularioCometido}</p>
                ) : (
                  <p className="mt-0.5 text-xs text-muted-foreground">
                    Datos que debe completar el cliente
                  </p>
                )}
                {formularioCompleto && (
                  <div className="mt-2 flex gap-2 rounded-lg border border-emerald-200 bg-emerald-50/80 p-2.5 text-xs text-emerald-900">
                    <CheckCircle2 className="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600" />
                    Formulario completo y listo.
                  </div>
                )}
              </div>
              {!soloLectura && (
                <div className="flex flex-wrap gap-1.5">
                  <Button type="button" size="sm" variant="outline" onClick={openFormEdit}>
                    <Pencil className="mr-1.5 h-3.5 w-3.5" />
                    Gestionar
                  </Button>
                  <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                    disabled={eliminarFormularioMutation.isPending}
                    onClick={handleEliminarFormulario}
                  >
                    {eliminarFormularioMutation.isPending ? (
                      <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                    ) : (
                      <Trash2 className="mr-1.5 h-3.5 w-3.5" />
                    )}
                    Eliminar sección
                  </Button>
                </div>
              )}
            </div>
            <ul className="space-y-2">
              {campos.map((c) => {
                const completado = Boolean(c.valor?.trim());
                return (
                  <li
                    key={c.id}
                    className={cn(
                      'flex flex-wrap items-center gap-3 rounded-xl border px-3 py-2.5',
                      completado
                        ? 'border-emerald-200 bg-emerald-50/40'
                        : 'border-border bg-card',
                    )}
                  >
                    <div
                      className={cn(
                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-md',
                        completado
                          ? 'bg-emerald-100 text-emerald-700'
                          : 'bg-primary/10 text-primary',
                      )}
                    >
                      <ClipboardList className="h-4 w-4" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-medium text-foreground">{c.etiqueta}</p>
                      <p className="text-xs text-muted-foreground">{c.tipoLabel}</p>
                    </div>
                    {completado ? (
                      <span className="inline-flex max-w-[12rem] items-center gap-1.5 truncate text-xs text-emerald-700">
                        <CheckCircle2 className="h-3.5 w-3.5 shrink-0" />
                        <span className="truncate">{c.valor}</span>
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1.5 text-xs text-amber-700">
                        <CircleAlert className="h-3.5 w-3.5 shrink-0" />
                        Pendiente
                      </span>
                    )}
                    {!soloLectura && (
                      <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                        disabled={eliminarCampoMutation.isPending}
                        onClick={() => handleEliminarCampo(c.id, c.etiqueta)}
                        aria-label={`Eliminar campo ${c.etiqueta}`}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    )}
                  </li>
                );
              })}
            </ul>
          </div>
        )}

        {req.tieneArchivo && req.tipo === 'escrito' && (
          <div className="flex items-center gap-2 border-t border-border bg-card px-3 py-3 text-sm">
            <ScrollText className="h-4 w-4 text-muted-foreground" />
            <span>{req.archivoNombre ?? 'Escrito vinculado'}</span>
            <Badge variant="outline">Escrito</Badge>
          </div>
        )}

        {soloLectura && docs.length === 0 && campos.length === 0 && !req.tieneArchivo && (
          <p className="p-3 text-sm text-muted-foreground">
            Este requerimiento no tiene entregables registrados.
          </p>
        )}
      </div>

      {!soloLectura && (
        <>
          <DocumentoModal
            open={docModalOpen}
            onOpenChange={setDocModalOpen}
            expedienteId={expedienteId}
            reqId={req.id}
            onSuccess={onDone}
          />
          <FormularioModal
            open={formModalOpen}
            onOpenChange={setFormModalOpen}
            mode={formModalMode}
            expedienteId={expedienteId}
            req={req}
            onSuccess={onDone}
          />
          <EscritoModal
            open={escritoModalOpen}
            onOpenChange={setEscritoModalOpen}
            expedienteId={expedienteId}
            req={req}
            escritos={escritos}
            onSuccess={onDone}
          />
        </>
      )}
    </>
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
  const [presentacion, setPresentacion] = useState<File | null>(null);
  const [justificante, setJustificante] = useState<File | null>(null);
  const [presentarOpen, setPresentarOpen] = useState(false);
  const modalPresentacionId = useId();
  const modalJustificanteId = useId();

  const cerrado = requerimientoCerrado(req.estado);
  const esEscrito = req.tipo === 'escrito';
  const tieneItems =
    (req.documentos?.length ?? 0) > 0 || (req.campos?.length ?? 0) > 0;
  const flujoEntregables = !esEscrito;
  const puedePresentarEnMercurio = flujoEntregables
    ? tieneItems
      ? req.listoParaPresentar
      : true
    : req.tieneArchivo;
  const puedeConfirmarPresentacion = flujoEntregables
    ? Boolean(presentacion) && Boolean(justificante)
    : Boolean(justificante);

  const presentarMutation = useMutation({
    mutationFn: () => {
      if (!justificante) throw new Error('Adjunte el justificante de presentación');
      if (flujoEntregables) {
        if (!presentacion) throw new Error('Adjunte el documento de presentación en Mercurio');
        if (tieneItems && !req.listoParaPresentar) {
          throw new Error('Complete y valide todos los documentos y campos antes de presentar');
        }
        return api.presentarRequerimientoMercurio(expedienteId, req.id, justificante, {
          presentacion,
        });
      }
      if (!req.tieneArchivo) {
        throw new Error('Vincule un escrito antes de presentar');
      }
      return api.presentarRequerimientoMercurio(expedienteId, req.id, justificante);
    },
    onSuccess: () => {
      setPresentacion(null);
      setJustificante(null);
      setPresentarOpen(false);
      onDone();
    },
  });

  return (
    <li
      className={cn(
        'rounded-lg border border-border',
        open && !cerrado && 'border-primary/30 bg-primary/5',
        cerrado && open && 'border-emerald-200 bg-emerald-50/20',
        cerrado && !open && 'border-border bg-muted/20',
      )}
    >
      <button
        type="button"
        className="flex w-full items-start justify-between gap-3 px-4 py-3 text-left hover:bg-muted/40"
        onClick={onToggle}
        aria-expanded={open}
      >
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <p className="text-sm font-medium">{req.nombre}</p>
            {cerrado && <Badge variant="success">{req.estadoLabel}</Badge>}
          </div>
          {cerrado && (
            <p className="mt-0.5 text-xs text-muted-foreground">
              {req.fechaPresentacion
                ? `Presentación y justificante · ${formatFechaPresentacion(req.fechaPresentacion)}`
                : 'Pulse para ver el resumen de gestiones'}
            </p>
          )}
        </div>
        <ChevronDown
          className={cn(
            'mt-0.5 h-4 w-4 shrink-0 text-muted-foreground transition-transform',
            open && 'rotate-180',
          )}
          aria-hidden
        />
      </button>
      {open && (
        <div className="space-y-4 border-t border-border px-4 py-3">
          {req.descripcion && <p className="text-sm text-muted-foreground">{req.descripcion}</p>}

          <EntregablesSection
            req={req}
            expedienteId={expedienteId}
            onDone={onDone}
            soloLectura={cerrado}
          />

          {cerrado && (req.tieneArchivo || req.tieneJustificante) && (
            <div className="space-y-3 border-t border-border pt-3">
              <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                Comprobantes de presentación
              </p>
              <div className="grid gap-3 sm:grid-cols-2">
                {req.tieneArchivo && (
                  <ComprobantePresentacionCard
                    title="Presentación"
                    description={req.archivoNombre ?? 'Documento presentado en Mercurio'}
                    fecha={req.fechaPresentacion}
                    onVer={() =>
                      void openAuthenticatedDocument(
                        api.tramitacionRequerimientoArchivoUrl(expedienteId, req.id),
                      )
                    }
                  />
                )}
                {req.tieneJustificante && (
                  <ComprobantePresentacionCard
                    title="Justificante"
                    description="Justificante de Mercurio"
                    fecha={req.fechaPresentacion}
                    onVer={() =>
                      void openAuthenticatedDocument(
                        api.tramitacionRequerimientoJustificanteUrl(expedienteId, req.id),
                      )
                    }
                  />
                )}
              </div>
            </div>
          )}

          {!cerrado && (
            <>
              <div className="flex justify-end border-t border-border pt-3">
                <Button
                  disabled={!puedePresentarEnMercurio || presentarMutation.isPending}
                  onClick={() => setPresentarOpen(true)}
                >
                  {presentarMutation.isPending ? (
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  ) : (
                    <CheckCircle2 className="mr-2 h-4 w-4" />
                  )}
                  Presentar en Mercurio
                </Button>
              </div>

              <Dialog
                open={presentarOpen}
                onOpenChange={(nextOpen) => {
                  setPresentarOpen(nextOpen);
                  if (!nextOpen) {
                    setPresentacion(null);
                    setJustificante(null);
                  }
                }}
              >
                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>Presentar requerimiento</DialogTitle>
                    <DialogDescription>
                      Adjunte el documento presentado y el justificante de Mercurio.
                    </DialogDescription>
                  </DialogHeader>
                  <div className="space-y-4 py-2">
                    {flujoEntregables && (
                      <div className="space-y-2">
                        <Label htmlFor={modalPresentacionId}>Documento de presentación</Label>
                        <Input
                          id={modalPresentacionId}
                          type="file"
                          accept=".pdf,application/pdf"
                          onChange={(e) => setPresentacion(e.target.files?.[0] ?? null)}
                        />
                      </div>
                    )}
                    <div className="space-y-2">
                      <Label htmlFor={modalJustificanteId}>Justificante</Label>
                      <Input
                        id={modalJustificanteId}
                        type="file"
                        accept=".pdf,application/pdf"
                        onChange={(e) => setJustificante(e.target.files?.[0] ?? null)}
                      />
                    </div>
                  </div>
                  <DialogFooter>
                    <Button variant="ghost" onClick={() => setPresentarOpen(false)}>
                      Cancelar
                    </Button>
                    <Button
                      disabled={!puedeConfirmarPresentacion || presentarMutation.isPending}
                      onClick={() => presentarMutation.mutate()}
                    >
                      {presentarMutation.isPending ? (
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      ) : null}
                      Confirmar presentación
                    </Button>
                  </DialogFooter>
                </DialogContent>
              </Dialog>

              {presentarMutation.error && (
                <p className="text-sm text-destructive">
                  {presentarMutation.error instanceof Error
                    ? presentarMutation.error.message
                    : 'Error'}
                </p>
              )}
            </>
          )}

        </div>
      )}
    </li>
  );
}
