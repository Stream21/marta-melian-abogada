import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  AlertCircle,
  CheckCircle2,
  FileImage,
  Flag,
  Home,
  Info,
  Loader2,
  Phone,
  User,
  Users,
  X,
} from 'lucide-react';
import { api, fetchAuthenticatedAsset, isClienteDuplicadoError, type ClienteInput, type ClienteResponse } from '@/api/client';
import { ClienteDuplicadoConfirmDialog } from '@/components/cliente/ClienteDuplicadoConfirmDialog';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { ESTADOS_CIVILES } from '@/lib/cliente-datos';
import { ETIQUETAS_CAMPO_CLIENTE } from '@/lib/campos-devolucion';
import { cn } from '@/lib/utils';

interface CampoDef {
  key: keyof ClienteInput;
  label: string;
  value: string;
  obligatorio?: boolean;
  tipo?: 'text' | 'date' | 'email' | 'select-estado' | 'select-nacionalidad';
}

function isFilled(value: string | null | undefined): boolean {
  return value != null && String(value).trim() !== '';
}

function formatFechaDisplay(fecha: string | null | undefined): string {
  if (!isFilled(fecha)) return '';
  const d = new Date(fecha as string);
  if (Number.isNaN(d.getTime())) return fecha as string;
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
}

function clienteAInput(c: ClienteResponse): ClienteInput {
  return {
    nombre: c.nombre ?? '',
    nacionalidad: c.nacionalidad ?? '',
    tipoDocumento: c.tipoDocumento ?? '',
    numDocumento: c.numDocumento ?? '',
    fechaNacimiento: c.fechaNacimiento,
    lugarNacimiento: c.lugarNacimiento ?? '',
    estadoCivil: c.estadoCivil ?? '',
    domicilio: c.domicilio ?? '',
    codigoPostal: c.codigoPostal ?? '',
    ciudad: c.ciudad ?? '',
    provincia: c.provincia ?? '',
    nombrePadre: c.nombrePadre ?? '',
    nombreMadre: c.nombreMadre ?? '',
    telefono: c.telefono ?? '',
    email: c.email ?? '',
  };
}

function CampoRevision({
  campo,
  editando,
  draft,
  marcado,
  nacionalidades,
  onEmpezarEdicion,
  onDraftChange,
  onGuardar,
  onCancelar,
  onToggleMarcado,
  guardando,
}: {
  campo: CampoDef;
  editando: boolean;
  draft: string;
  marcado: boolean;
  nacionalidades: { codigo: string; nombre: string }[];
  onEmpezarEdicion: () => void;
  onDraftChange: (v: string) => void;
  onGuardar: (valor?: string) => void;
  onCancelar: () => void;
  onToggleMarcado: () => void;
  guardando: boolean;
}) {
  const completo = isFilled(campo.value);
  const display =
    campo.tipo === 'date' && completo ? formatFechaDisplay(campo.value) : campo.value;

  const persistirAlSalir = () => {
    if (guardando) return;
    onGuardar();
  };

  return (
    <div
      className={cn(
        'rounded-lg border px-3 py-2.5 transition-colors',
        marcado
          ? 'border-amber-400 bg-amber-50/70 ring-1 ring-amber-300/60'
          : completo
            ? 'border-emerald-200/80 bg-emerald-50/40'
            : 'border-amber-200/80 bg-amber-50/30',
      )}
    >
      <div className="mb-1 flex items-start justify-between gap-2">
        <span className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
          {campo.label}
          {campo.obligatorio === false && (
            <span className="normal-case font-normal text-muted-foreground/70"> (opc.)</span>
          )}
        </span>
        <div className="flex shrink-0 items-center gap-1">
          <button
            type="button"
            title={
              marcado
                ? 'El cliente deberá corregir este campo al devolver el paso'
                : 'Al devolver el paso, el cliente verá este campo resaltado para corregirlo'
            }
            onClick={(e) => {
              e.stopPropagation();
              onToggleMarcado();
            }}
            className={cn(
              'inline-flex h-7 items-center gap-1 rounded-md border px-1.5 text-[10px] font-semibold transition-colors',
              marcado
                ? 'border-amber-400 bg-amber-100 text-amber-900'
                : 'border-border bg-card text-muted-foreground hover:border-amber-300 hover:text-amber-800',
            )}
          >
            <Flag className="h-3 w-3" />
            {marcado ? 'Pedido al cliente' : 'Pedir al cliente'}
          </button>
          {completo ? (
            <Badge variant="success" className="gap-1 px-2 py-0 text-[10px]">
              <CheckCircle2 className="h-3 w-3" />
              Sí
            </Badge>
          ) : (
            <Badge variant="warning" className="gap-1 px-2 py-0 text-[10px]">
              <AlertCircle className="h-3 w-3" />
              No
            </Badge>
          )}
        </div>
      </div>

      {editando ? (
        <div className="space-y-1.5" onClick={(e) => e.stopPropagation()}>
          {campo.tipo === 'select-estado' ? (
            <select
              className="input-field h-9 w-full"
              value={draft}
              autoFocus
              disabled={guardando}
              onChange={(e) => {
                onDraftChange(e.target.value);
                onGuardar(e.target.value);
              }}
              onBlur={persistirAlSalir}
            >
              <option value="">Seleccionar…</option>
              {ESTADOS_CIVILES.map((e) => (
                <option key={e.value} value={e.value}>
                  {e.label}
                </option>
              ))}
            </select>
          ) : campo.tipo === 'select-nacionalidad' ? (
            <select
              className="input-field h-9 w-full"
              value={draft}
              autoFocus
              disabled={guardando}
              onChange={(e) => {
                onDraftChange(e.target.value);
                onGuardar(e.target.value);
              }}
              onBlur={persistirAlSalir}
            >
              <option value="">Seleccionar…</option>
              {draft && !nacionalidades.some((n) => n.nombre === draft) && (
                <option value={draft}>{draft}</option>
              )}
              {nacionalidades.map((n) => (
                <option key={n.codigo} value={n.nombre}>
                  {n.nombre}
                </option>
              ))}
            </select>
          ) : (
            <Input
              type={campo.tipo === 'date' ? 'date' : campo.tipo === 'email' ? 'email' : 'text'}
              value={draft}
              onChange={(e) => onDraftChange(e.target.value)}
              autoFocus
              disabled={guardando}
              onBlur={persistirAlSalir}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  e.preventDefault();
                  (e.target as HTMLInputElement).blur();
                }
                if (e.key === 'Escape') {
                  e.preventDefault();
                  onCancelar();
                }
              }}
            />
          )}
          <p className="text-[10px] text-muted-foreground">
            {guardando ? 'Guardando…' : 'Enter o clic fuera para guardar · Esc para cancelar'}
          </p>
        </div>
      ) : (
        <button
          type="button"
          onClick={onEmpezarEdicion}
          className="w-full text-left"
          title="Clic para editar"
        >
          <p
            className={cn(
              'text-sm leading-snug break-words',
              completo ? 'font-medium text-foreground' : 'italic text-muted-foreground',
            )}
          >
            {completo ? display : 'Sin datos — clic para completar'}
          </p>
        </button>
      )}
    </div>
  );
}

function SeccionRevision({
  titulo,
  icon: Icon,
  children,
}: {
  titulo: string;
  icon: typeof User;
  children: ReactNode;
}) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-card">
      <div className="flex items-center gap-2 border-b bg-muted/40 px-4 py-2.5">
        <Icon className="h-4 w-4 text-primary" />
        <h4 className="text-sm font-semibold">{titulo}</h4>
      </div>
      <div className="grid gap-2 p-3 sm:grid-cols-2">{children}</div>
    </section>
  );
}

function DocumentoIdentidadPreview({ url, title }: { url: string; title: string }) {
  const [blobUrl, setBlobUrl] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [ampliado, setAmpliado] = useState(false);

  useEffect(() => {
    let revoked: string | null = null;
    setLoading(true);

    void fetchAuthenticatedAsset(url)
      .then((objectUrl) => {
        revoked = objectUrl;
        setBlobUrl(objectUrl);
      })
      .finally(() => setLoading(false));

    return () => {
      if (revoked) URL.revokeObjectURL(revoked);
    };
  }, [url]);

  useEffect(() => {
    if (!ampliado) return;
    // Capture: el Dialog de revisión también escucha Escape y cerraría el modal entero.
    const onKey = (e: KeyboardEvent) => {
      if (e.key !== 'Escape') return;
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      setAmpliado(false);
    };
    window.addEventListener('keydown', onKey, true);
    return () => window.removeEventListener('keydown', onKey, true);
  }, [ampliado]);

  const cerrarAmpliado = () => setAmpliado(false);

  return (
    <>
      <button
        type="button"
        onClick={() => blobUrl && setAmpliado(true)}
        className="group relative flex h-36 w-full items-center justify-center overflow-hidden rounded-lg border bg-muted/20 transition hover:border-primary/40 hover:shadow-sm"
      >
        {loading && <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />}
        {!loading && blobUrl && (
          <img src={blobUrl} alt={title} className="max-h-full max-w-full object-contain p-1" />
        )}
        {!loading && !blobUrl && (
          <span className="px-2 text-center text-xs text-muted-foreground">No disponible</span>
        )}
        {blobUrl && (
          <span className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent py-1.5 text-[10px] font-medium text-white opacity-0 transition group-hover:opacity-100">
            Clic para ampliar
          </span>
        )}
      </button>
      {ampliado &&
        blobUrl &&
        createPortal(
          <div
            data-image-lightbox=""
            className="pointer-events-auto fixed inset-0 z-[200] flex items-center justify-center bg-black/80 p-4"
            onPointerDown={(e) => {
              e.stopPropagation();
              cerrarAmpliado();
            }}
            role="presentation"
          >
            <button
              type="button"
              onPointerDown={(e) => {
                e.stopPropagation();
                cerrarAmpliado();
              }}
              className="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-background/90 text-foreground shadow-md transition hover:bg-background"
              aria-label="Cerrar imagen ampliada"
            >
              <X className="h-5 w-5" />
            </button>
            <img
              src={blobUrl}
              alt={title}
              className="max-h-[90vh] max-w-full rounded-lg shadow-2xl"
              onPointerDown={(e) => {
                e.stopPropagation();
                cerrarAmpliado();
              }}
            />
            <p className="pointer-events-none absolute bottom-4 left-1/2 -translate-x-1/2 rounded-md bg-background/80 px-3 py-1 text-xs text-muted-foreground">
              Clic o Escape para cerrar
            </p>
          </div>,
          document.body,
        )}
    </>
  );
}

function buildCampos(c: ClienteResponse): CampoDef[] {
  return [
    { key: 'nombre', label: ETIQUETAS_CAMPO_CLIENTE.nombre, value: c.nombre ?? '' },
    {
      key: 'nacionalidad',
      label: ETIQUETAS_CAMPO_CLIENTE.nacionalidad,
      value: c.nacionalidad ?? '',
      tipo: 'select-nacionalidad',
    },
    { key: 'tipoDocumento', label: ETIQUETAS_CAMPO_CLIENTE.tipoDocumento, value: c.tipoDocumento ?? '' },
    { key: 'numDocumento', label: ETIQUETAS_CAMPO_CLIENTE.numDocumento, value: c.numDocumento ?? '' },
    {
      key: 'fechaNacimiento',
      label: ETIQUETAS_CAMPO_CLIENTE.fechaNacimiento,
      value: c.fechaNacimiento ?? '',
      tipo: 'date',
    },
    { key: 'lugarNacimiento', label: ETIQUETAS_CAMPO_CLIENTE.lugarNacimiento, value: c.lugarNacimiento ?? '' },
    {
      key: 'estadoCivil',
      label: ETIQUETAS_CAMPO_CLIENTE.estadoCivil,
      value: c.estadoCivil ?? '',
      tipo: 'select-estado',
    },
    { key: 'telefono', label: ETIQUETAS_CAMPO_CLIENTE.telefono, value: c.telefono ?? '' },
    { key: 'email', label: ETIQUETAS_CAMPO_CLIENTE.email, value: c.email ?? '', tipo: 'email' },
    { key: 'domicilio', label: ETIQUETAS_CAMPO_CLIENTE.domicilio, value: c.domicilio ?? '' },
    { key: 'codigoPostal', label: ETIQUETAS_CAMPO_CLIENTE.codigoPostal, value: c.codigoPostal ?? '' },
    { key: 'ciudad', label: ETIQUETAS_CAMPO_CLIENTE.ciudad, value: c.ciudad ?? '' },
    { key: 'provincia', label: ETIQUETAS_CAMPO_CLIENTE.provincia, value: c.provincia ?? '' },
    {
      key: 'nombrePadre',
      label: ETIQUETAS_CAMPO_CLIENTE.nombrePadre,
      value: c.nombrePadre ?? '',
      obligatorio: false,
    },
    {
      key: 'nombreMadre',
      label: ETIQUETAS_CAMPO_CLIENTE.nombreMadre,
      value: c.nombreMadre ?? '',
      obligatorio: false,
    },
  ];
}

interface ClienteDatosRevisionPanelProps {
  expedienteId: string;
  clienteId: string;
  camposMarcados?: string[];
  onCamposMarcadosChange?: (campos: string[]) => void;
}

export function ClienteDatosRevisionPanel({
  expedienteId,
  clienteId,
  camposMarcados = [],
  onCamposMarcadosChange,
}: ClienteDatosRevisionPanelProps) {
  const queryClient = useQueryClient();
  const [editandoKey, setEditandoKey] = useState<keyof ClienteInput | null>(null);
  const [draft, setDraft] = useState('');
  const [duplicado, setDuplicado] = useState<{
    body: ClienteInput;
    nombre: string;
    campo?: string;
  } | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['cliente', clienteId],
    queryFn: () => api.getCliente(clienteId),
  });

  const { data: nacionalidades = [] } = useQuery({
    queryKey: ['nacionalidades'],
    queryFn: () => api.getNacionalidades(),
  });

  const saveMutation = useMutation({
    mutationFn: (body: ClienteInput & { permitirDuplicado?: boolean }) =>
      api.actualizarDatosClienteContratacion(expedienteId, body),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['cliente', clienteId] });
      void queryClient.invalidateQueries({ queryKey: ['contratacion', expedienteId] });
      setEditandoKey(null);
      setDuplicado(null);
    },
    onError: (error, variables) => {
      if (isClienteDuplicadoError(error) && !variables.permitirDuplicado) {
        const { permitirDuplicado: _, ...body } = variables;
        setDuplicado({
          body,
          nombre: error.clienteExistenteNombre ?? 'otro cliente',
          campo: error.campoDuplicado,
        });
      }
    },
  });

  const stats = useMemo(() => {
    if (!data?.cliente) return null;
    const campos = buildCampos(data.cliente);
    const obligatorios = campos.filter((c) => c.obligatorio !== false);
    const completados = obligatorios.filter((c) => isFilled(c.value));
    const doc = data.cliente.documentoIdentidad;
    return {
      total: obligatorios.length,
      completados: completados.length,
      pct: Math.round((completados.length / obligatorios.length) * 100),
      tieneAnverso: !!doc?.anversoUrl,
      tieneReverso: !!doc?.reversoUrl,
      campos,
    };
  }, [data]);

  const toggleMarcado = (key: string) => {
    if (!onCamposMarcadosChange) return;
    onCamposMarcadosChange(
      camposMarcados.includes(key)
        ? camposMarcados.filter((k) => k !== key)
        : [...camposMarcados, key],
    );
  };

  const empezarEdicion = (campo: CampoDef) => {
    setEditandoKey(campo.key);
    setDraft(campo.value);
  };

  const guardarCampo = (valorOverride?: string) => {
    if (!data?.cliente || !editandoKey || saveMutation.isPending) return;
    const key = editandoKey;
    const valor = valorOverride !== undefined ? valorOverride : draft;
    const body = clienteAInput(data.cliente);
    const previo =
      key === 'fechaNacimiento' ? (body.fechaNacimiento ?? '') : String(body[key] ?? '');

    if (String(valor) === String(previo)) {
      setEditandoKey(null);
      return;
    }

    const next: ClienteInput =
      key === 'fechaNacimiento'
        ? { ...body, fechaNacimiento: valor || null }
        : { ...body, [key]: valor };
    saveMutation.mutate(next);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center gap-2 py-12 text-muted-foreground">
        <Loader2 className="h-5 w-5 animate-spin" />
        Cargando ficha del cliente…
      </div>
    );
  }

  if (!data?.cliente || !stats) {
    return (
      <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
        No se encontraron datos del cliente.
      </div>
    );
  }

  const c = data.cliente;
  const doc = c.documentoIdentidad;
  const docsOk = stats.tieneAnverso;
  const todoCompleto = stats.pct === 100 && docsOk;
  const porKey = Object.fromEntries(stats.campos.map((campo) => [campo.key, campo])) as Record<
    string,
    CampoDef
  >;

  const renderCampo = (key: keyof ClienteInput) => {
    const campo = porKey[key];
    if (!campo) return null;
    return (
      <CampoRevision
        key={key}
        campo={campo}
        editando={editandoKey === key}
        draft={draft}
        marcado={camposMarcados.includes(key)}
        nacionalidades={nacionalidades}
        onEmpezarEdicion={() => empezarEdicion(campo)}
        onDraftChange={setDraft}
        onGuardar={guardarCampo}
        onCancelar={() => setEditandoKey(null)}
        onToggleMarcado={() => toggleMarcado(key)}
        guardando={saveMutation.isPending}
      />
    );
  };

  return (
    <div className="space-y-4">
      <div
        className={cn(
          'flex flex-wrap items-center justify-between gap-4 rounded-xl border p-4',
          todoCompleto ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/40',
        )}
      >
        <div>
          <p className="text-sm font-semibold">
            {todoCompleto ? 'Ficha completa' : 'Ficha con datos pendientes'}
          </p>
          <p className="mt-0.5 text-xs text-muted-foreground">
            {stats.completados} de {stats.total} campos obligatorios ·{' '}
            {docsOk ? 'Documento escaneado' : 'Falta escaneo del documento'}
            {camposMarcados.length > 0 && (
              <> · <span className="font-medium text-amber-800">{camposMarcados.length} marcados para el cliente</span></>
            )}
          </p>
        </div>
        <div className="flex items-center gap-3">
          <div className="h-2 w-32 overflow-hidden rounded-full bg-muted">
            <div
              className={cn('h-full rounded-full transition-all', todoCompleto ? 'bg-emerald-500' : 'bg-amber-500')}
              style={{ width: `${stats.pct}%` }}
            />
          </div>
          <span className="text-lg font-bold tabular-nums">{stats.pct}%</span>
        </div>
      </div>

      {saveMutation.isError && !isClienteDuplicadoError(saveMutation.error) && (
        <p className="text-sm text-destructive">{(saveMutation.error as Error).message}</p>
      )}

      <SeccionRevision titulo="Identidad personal" icon={User}>
        {renderCampo('nombre')}
        {renderCampo('nacionalidad')}
        {renderCampo('tipoDocumento')}
        {renderCampo('numDocumento')}
        {renderCampo('fechaNacimiento')}
        {renderCampo('lugarNacimiento')}
        {renderCampo('estadoCivil')}
      </SeccionRevision>

      <SeccionRevision titulo="Contacto" icon={Phone}>
        {renderCampo('telefono')}
        {renderCampo('email')}
      </SeccionRevision>

      <SeccionRevision titulo="Domicilio" icon={Home}>
        {renderCampo('domicilio')}
        {renderCampo('codigoPostal')}
        {renderCampo('ciudad')}
        {renderCampo('provincia')}
      </SeccionRevision>

      <SeccionRevision titulo="Filación" icon={Users}>
        {renderCampo('nombrePadre')}
        {renderCampo('nombreMadre')}
      </SeccionRevision>

      <section className="overflow-hidden rounded-xl border border-border bg-card">
        <div className="flex items-center justify-between border-b bg-muted/40 px-4 py-2.5">
          <div className="flex items-center gap-2">
            <FileImage className="h-4 w-4 text-primary" />
            <h4 className="text-sm font-semibold">Documento de identidad escaneado</h4>
          </div>
          <div className="flex gap-1.5">
            <Badge variant={stats.tieneAnverso ? 'success' : 'warning'} className="text-[10px]">
              Anverso {stats.tieneAnverso ? '✓' : '✗'}
            </Badge>
            {doc?.reversoUrl !== undefined && (
              <Badge variant={stats.tieneReverso ? 'success' : 'secondary'} className="text-[10px]">
                Reverso {stats.tieneReverso ? '✓' : '—'}
              </Badge>
            )}
          </div>
        </div>
        <div className="grid gap-3 p-3 sm:grid-cols-2">
          {doc?.anversoUrl ? (
            <div>
              <p className="mb-2 text-xs font-medium text-muted-foreground">Anverso</p>
              <DocumentoIdentidadPreview url={doc.anversoUrl} title="Anverso DNI/NIE" />
            </div>
          ) : (
            <div className="flex h-36 items-center justify-center rounded-lg border border-dashed border-amber-300 bg-amber-50/30 text-xs text-amber-800">
              <AlertCircle className="mr-1.5 h-4 w-4" />
              Anverso no escaneado
            </div>
          )}
          {doc?.reversoUrl ? (
            <div>
              <p className="mb-2 text-xs font-medium text-muted-foreground">Reverso</p>
              <DocumentoIdentidadPreview url={doc.reversoUrl} title="Reverso DNI/NIE" />
            </div>
          ) : (
            <div className="flex h-36 items-center justify-center rounded-lg border border-dashed bg-muted/30 text-xs text-muted-foreground">
              Reverso no aportado
            </div>
          )}
        </div>
      </section>

      <p className="flex items-start gap-2 px-1 text-xs text-muted-foreground">
        <Info className="mt-0.5 h-3.5 w-3.5 shrink-0" />
        <span>
          <strong className="text-foreground">Clic en un valor</strong> para editarlo (Enter o salir del
          campo guarda). <strong className="text-foreground">Pedir al cliente</strong> marca el dato
          para que, al devolver el paso, el cliente lo vea resaltado y lo corrija él.
        </span>
      </p>

      <ClienteDuplicadoConfirmDialog
        open={!!duplicado}
        clienteNombre={duplicado?.nombre ?? ''}
        campo={duplicado?.campo}
        loading={saveMutation.isPending}
        onCancel={() => setDuplicado(null)}
        onConfirm={() => {
          if (!duplicado) return;
          saveMutation.mutate({ ...duplicado.body, permitirDuplicado: true });
        }}
        confirmLabel="Continuar con este cliente"
      />
    </div>
  );
}
