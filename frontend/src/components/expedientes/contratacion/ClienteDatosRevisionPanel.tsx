import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  AlertCircle,
  CheckCircle2,
  FileImage,
  Home,
  Info,
  Loader2,
  Phone,
  User,
  Users,
} from 'lucide-react';
import { api, fetchAuthenticatedAsset, type ClienteResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

interface CampoDef {
  key: string;
  label: string;
  value: string | null | undefined;
  obligatorio?: boolean;
}

function isFilled(value: string | null | undefined): boolean {
  return value != null && String(value).trim() !== '';
}

function formatFecha(fecha: string | null | undefined): string {
  if (!isFilled(fecha)) return '';
  const d = new Date(fecha as string);
  if (Number.isNaN(d.getTime())) return fecha as string;
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
}

function CampoRevision({ label, value, obligatorio = true }: Omit<CampoDef, 'key'> & { value: string }) {
  const completo = isFilled(value);

  return (
    <div
      className={cn(
        'rounded-lg border px-3 py-2.5 transition-colors',
        completo ? 'border-emerald-200/80 bg-emerald-50/40' : 'border-amber-200/80 bg-amber-50/30',
      )}
    >
      <div className="flex items-start justify-between gap-2 mb-1">
        <span className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
          {label}
          {!obligatorio && <span className="normal-case font-normal text-muted-foreground/70"> (opc.)</span>}
        </span>
        {completo ? (
          <Badge variant="success" className="shrink-0 gap-1 px-2 py-0 text-[10px]">
            <CheckCircle2 className="h-3 w-3" />
            Sí
          </Badge>
        ) : (
          <Badge variant="warning" className="shrink-0 gap-1 px-2 py-0 text-[10px]">
            <AlertCircle className="h-3 w-3" />
            No
          </Badge>
        )}
      </div>
      <p
        className={cn(
          'text-sm leading-snug break-words',
          completo ? 'font-medium text-foreground' : 'italic text-muted-foreground',
        )}
      >
        {completo ? value : 'Sin datos — el cliente no lo ha indicado'}
      </p>
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
    <section className="rounded-xl border border-border bg-card overflow-hidden">
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
          <span className="text-xs text-muted-foreground px-2 text-center">No disponible</span>
        )}
        {blobUrl && (
          <span className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent py-1.5 text-[10px] font-medium text-white opacity-0 transition group-hover:opacity-100">
            Clic para ampliar
          </span>
        )}
      </button>
      {ampliado && blobUrl && (
        <div
          className="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
          onClick={() => setAmpliado(false)}
          onKeyDown={(e) => e.key === 'Escape' && setAmpliado(false)}
          role="button"
          tabIndex={0}
        >
          <img
            src={blobUrl}
            alt={title}
            className="max-h-[90vh] max-w-full rounded-lg shadow-2xl"
            onClick={(e) => e.stopPropagation()}
          />
        </div>
      )}
    </>
  );
}

function buildCampos(c: ClienteResponse): CampoDef[] {
  return [
    { key: 'nombre', label: 'Nombre completo', value: c.nombre },
    { key: 'nacionalidad', label: 'Nacionalidad', value: c.nacionalidad },
    { key: 'tipoDocumento', label: 'Tipo de documento', value: c.tipoDocumento },
    { key: 'numDocumento', label: 'Número de documento', value: c.numDocumento },
    { key: 'fechaNacimiento', label: 'Fecha de nacimiento', value: formatFecha(c.fechaNacimiento) },
    { key: 'lugarNacimiento', label: 'Lugar de nacimiento', value: c.lugarNacimiento },
    { key: 'estadoCivil', label: 'Estado civil', value: c.estadoCivil },
    { key: 'telefono', label: 'Teléfono', value: c.telefono },
    { key: 'email', label: 'Correo electrónico', value: c.email },
    { key: 'domicilio', label: 'Domicilio', value: c.domicilio },
    { key: 'codigoPostal', label: 'Código postal', value: c.codigoPostal },
    { key: 'ciudad', label: 'Ciudad', value: c.ciudad },
    { key: 'provincia', label: 'Provincia', value: c.provincia },
    { key: 'nombrePadre', label: 'Nombre del padre', value: c.nombrePadre, obligatorio: false },
    { key: 'nombreMadre', label: 'Nombre de la madre', value: c.nombreMadre, obligatorio: false },
  ];
}

export function ClienteDatosRevisionPanel({ clienteId }: { clienteId: string }) {
  const { data, isLoading } = useQuery({
    queryKey: ['cliente', clienteId],
    queryFn: () => api.getCliente(clienteId),
  });

  const stats = useMemo(() => {
    if (!data?.cliente) return null;
    const campos = buildCampos(data.cliente);
    const obligatorios = campos.filter((c) => c.obligatorio !== false);
    const completados = obligatorios.filter((c) => isFilled(c.value));
    const doc = data.cliente.documentoIdentidad;
    const tieneAnverso = !!doc?.anversoUrl;
    const tieneReverso = !!doc?.reversoUrl;

    return {
      total: obligatorios.length,
      completados: completados.length,
      pct: Math.round((completados.length / obligatorios.length) * 100),
      tieneAnverso,
      tieneReverso,
      campos,
    };
  }, [data]);

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

  return (
    <div className="space-y-4">
      <div
        className={cn(
          'rounded-xl border p-4 flex flex-wrap items-center justify-between gap-4',
          todoCompleto ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/40',
        )}
      >
        <div>
          <p className="text-sm font-semibold">
            {todoCompleto ? 'Ficha completa' : 'Ficha con datos pendientes'}
          </p>
          <p className="text-xs text-muted-foreground mt-0.5">
            {stats.completados} de {stats.total} campos obligatorios ·{' '}
            {docsOk ? 'DNI escaneado' : 'Falta escaneo DNI'}
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

      <SeccionRevision titulo="Identidad personal" icon={User}>
        {stats.campos.slice(0, 7).map((campo) => (
          <CampoRevision
            key={campo.key}
            label={campo.label}
            value={campo.value ?? ''}
            obligatorio={campo.obligatorio}
          />
        ))}
      </SeccionRevision>

      <SeccionRevision titulo="Contacto" icon={Phone}>
        <CampoRevision label="Teléfono" value={c.telefono} />
        <CampoRevision label="Correo electrónico" value={c.email} />
      </SeccionRevision>

      <SeccionRevision titulo="Domicilio" icon={Home}>
        <CampoRevision label="Domicilio" value={c.domicilio} />
        <CampoRevision label="Código postal" value={c.codigoPostal} />
        <CampoRevision label="Ciudad" value={c.ciudad} />
        <CampoRevision label="Provincia" value={c.provincia} />
      </SeccionRevision>

      <SeccionRevision titulo="Filación" icon={Users}>
        <CampoRevision label="Nombre del padre" value={c.nombrePadre} obligatorio={false} />
        <CampoRevision label="Nombre de la madre" value={c.nombreMadre} obligatorio={false} />
      </SeccionRevision>

      <section className="rounded-xl border border-border bg-card overflow-hidden">
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

      <p className="flex items-start gap-2 text-xs text-muted-foreground px-1">
        <Info className="mt-0.5 h-3.5 w-3.5 shrink-0" />
        <span>
          <strong className="text-foreground">Sí</strong> = el cliente ha indicado el dato.{' '}
          <strong className="text-foreground">No</strong> = campo vacío; considere devolver el paso con una nota
          indicando qué debe completar.
        </span>
      </p>
    </div>
  );
}