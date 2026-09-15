import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
  AlertTriangle,
  CheckCircle2,
  ChevronDown,
  Clock,
  FileWarning,
  Upload,
} from 'lucide-react';
import { api, type AccesoDocumentacionDocumentoResponse } from '@/api/client';
import { DocumentoArchivoUploadControl } from '@/components/cliente-portal/DocumentoArchivoUploadControl';
import {
  ActiveDocumentUploadsPanel,
  type ActiveDocumentUpload,
} from '@/components/cliente-portal/ActiveDocumentUploadsPanel';
import { DocumentoArchivosSubidosList } from '@/components/cliente-portal/DocumentoArchivosSubidosList';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

interface DocumentacionUploadPanelProps {
  token: string;
  documentos: AccesoDocumentacionDocumentoResponse[];
}

type GrupoId = 'accion' | 'revision' | 'completados';

function estadoLabel(doc: AccesoDocumentacionDocumentoResponse) {
  if (doc.estadoLabel) return doc.estadoLabel;
  switch (doc.estado) {
    case 'validado':
      return doc.subidoPor === 'abogado' ? 'Aportado por su abogado' : 'Validado';
    case 'entregado':
      return 'En revisión';
    case 'rechazado':
      return 'Devuelto';
    default:
      return 'Pendiente';
  }
}

function clasificarGrupo(doc: AccesoDocumentacionDocumentoResponse): GrupoId {
  if (doc.estado === 'validado') return 'completados';
  if (doc.estado === 'entregado') return 'revision';
  // Lo gestiona el abogado: no mezclarlo con lo que el cliente debe subir.
  if (doc.responsableActual === 'abogado' && !doc.puedeSubir) return 'revision';
  return 'accion';
}

function SeccionGrupo({
  titulo,
  contador,
  tono,
  children,
  defaultOpen = true,
}: {
  titulo: string;
  contador: number;
  tono: 'accion' | 'revision' | 'completados';
  children: ReactNode;
  defaultOpen?: boolean;
}) {
  const [open, setOpen] = useState(defaultOpen);

  if (contador === 0) return null;

  const tonoClass =
    tono === 'accion'
      ? 'text-primary'
      : tono === 'revision'
        ? 'text-amber-800'
        : 'text-emerald-800';

  return (
    <section className="space-y-3">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="flex w-full items-center justify-between gap-3 rounded-lg px-0.5 py-1 text-left"
        aria-expanded={open}
      >
        <div className="flex min-w-0 items-center gap-2">
          <h3 className={cn('text-sm font-bold uppercase tracking-wide', tonoClass)}>
            {titulo}
          </h3>
          <span className="rounded-full bg-muted px-2 py-0.5 text-xs font-semibold tabular-nums text-muted-foreground">
            {contador}
          </span>
        </div>
        <ChevronDown
          className={cn(
            'h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200',
            open && 'rotate-180',
          )}
        />
      </button>
      {open && <ul className="space-y-3">{children}</ul>}
    </section>
  );
}

function DocumentoRequisitoCard({
  doc,
  uploading,
  uploadVersion,
  errorMessage,
  onUpload,
  expanded,
  onToggle,
}: {
  doc: AccesoDocumentacionDocumentoResponse;
  uploading: boolean;
  uploadVersion: number;
  errorMessage: string | null;
  onUpload: (files: File[]) => void;
  expanded: boolean;
  onToggle: () => void;
}) {
  const enRevision = doc.estado === 'entregado';
  const validado = doc.estado === 'validado';
  const rechazado = doc.estado === 'rechazado';
  const pendiente = doc.estado === 'pendiente';
  const aportadoPorAbogado = validado && doc.subidoPor === 'abogado';
  const validadoTrasRevision = validado && doc.subidoPor !== 'abogado';
  const gestionadoPorAbogado =
    doc.responsableActual === 'abogado' && pendiente && !doc.puedeSubir;
  const derivadoAlCliente =
    doc.responsableActual === 'cliente' && doc.parcialConArchivos && pendiente;
  const puedeSubir = doc.puedeSubir;
  const requiereAccion = puedeSubir || rechazado;

  return (
    <li
      className={cn(
        'overflow-hidden rounded-xl border bg-card shadow-sm',
        requiereAccion && !rechazado && 'border-primary/30 ring-1 ring-primary/10',
        rechazado && 'border-red-300 ring-1 ring-red-100',
        enRevision && 'border-amber-200',
        validado && 'border-border bg-muted/20 shadow-none',
      )}
    >
      <button
        type="button"
        onClick={onToggle}
        aria-expanded={expanded}
        className={cn(
          'flex w-full items-start gap-3 px-4 py-3.5 text-left transition-colors',
          requiereAccion && !rechazado && 'bg-primary/5',
          rechazado && 'bg-red-50/80',
          enRevision && 'bg-amber-50/50',
          expanded && 'border-b border-border/60',
        )}
      >
        <div
          className={cn(
            'mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
            rechazado && 'bg-red-100 text-red-700',
            requiereAccion && !rechazado && 'bg-primary text-primary-foreground',
            enRevision && 'bg-amber-100 text-amber-800',
            validado && 'bg-emerald-100 text-emerald-700',
            !requiereAccion && !enRevision && !validado && 'bg-muted text-muted-foreground',
          )}
        >
          {validado ? (
            <CheckCircle2 className="h-4 w-4" />
          ) : enRevision ? (
            <Clock className="h-4 w-4" />
          ) : rechazado ? (
            <FileWarning className="h-4 w-4" />
          ) : (
            <Upload className="h-4 w-4" />
          )}
        </div>

        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-2">
            <h4 className="text-base font-semibold leading-snug text-foreground">
              {doc.nombre}
              {doc.obligatorio && requiereAccion && (
                <span className="ml-1 text-amber-700" title="Obligatorio" aria-label="Obligatorio">
                  *
                </span>
              )}
            </h4>
          </div>
          {!pendiente && (
            <div className="mt-1.5">
              <Badge
                variant={
                  validado
                    ? 'success'
                    : enRevision
                      ? 'warning'
                      : rechazado
                        ? 'destructive'
                        : 'secondary'
                }
              >
                {estadoLabel(doc)}
              </Badge>
            </div>
          )}
          {!expanded && doc.descripcion && requiereAccion && (
            <p className="mt-1.5 line-clamp-1 text-sm text-muted-foreground">
              {doc.descripcion}
            </p>
          )}
          {!expanded && puedeSubir && (
            <p className="mt-1.5 text-xs font-semibold text-primary">
              Pulse para abrir · elegir archivo · enviar
            </p>
          )}
        </div>

        <ChevronDown
          className={cn(
            'mt-2 h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200',
            expanded && 'rotate-180',
          )}
        />
      </button>

      {expanded && (
        <div className="space-y-3 px-4 py-3.5">
          {doc.descripcion && requiereAccion && (
            <p className="text-sm leading-relaxed text-muted-foreground">{doc.descripcion}</p>
          )}

          {(doc.archivos ?? []).length > 0 && (
            <DocumentoArchivosSubidosList archivos={doc.archivos ?? []} />
          )}

          {enRevision && (
            <div className="flex gap-2 rounded-lg border border-amber-200 bg-amber-50/80 p-3 text-sm text-amber-950">
              <Clock className="mt-0.5 h-4 w-4 shrink-0 text-amber-700" />
              <div>
                <p className="font-semibold">Enviado correctamente</p>
                <p className="mt-0.5 text-amber-900/90">
                  Su abogado lo revisará. No podrá modificarlo hasta que lo valide o lo devuelva.
                </p>
              </div>
            </div>
          )}

          {derivadoAlCliente && (
            <div className="flex gap-2 rounded-lg border border-primary/20 bg-primary/5 p-3 text-sm">
              <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
              <p>
                Su abogado aportó parte de la documentación. Complete lo que falta en este
                documento.
              </p>
            </div>
          )}

          {gestionadoPorAbogado && (
            <p className="text-sm text-muted-foreground">
              Su abogado está gestionando este documento. No necesita subir nada por ahora.
            </p>
          )}

          {aportadoPorAbogado && (
            <p className="text-sm text-emerald-800">
              Su abogado ha aportado este documento. No necesita hacer nada más.
            </p>
          )}

          {validadoTrasRevision && (
            <p className="text-sm text-emerald-800">
              Validado por su abogado. Ya no necesita volver a subirlo.
            </p>
          )}

          {rechazado && doc.notaRechazo && (
            <div className="flex gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-900">
              <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
              <div>
                <p className="font-semibold">Motivo de la devolución</p>
                <p className="mt-1 whitespace-pre-wrap leading-relaxed">{doc.notaRechazo}</p>
              </div>
            </div>
          )}

          {puedeSubir && (
            <div className="rounded-lg bg-muted/20 p-1">
              <DocumentoArchivoUploadControl
                tipo={doc.tipo}
                maxImagenes={doc.maxImagenes}
                uploading={uploading}
                showProgressOverlay={false}
                suppressUploadingUi={uploading}
                uploadingTitle="Enviando a su abogado…"
                uploadingDescription="Convirtiendo sus archivos a PDF. No cierre esta página; puede tardar un poco con imágenes grandes."
                uploadSuccessKey={`${uploadVersion}-${doc.id}-${doc.estado}`}
                error={errorMessage}
                variant="default"
                readyLabel="Enviar a mi abogado"
                onUpload={onUpload}
              />
            </div>
          )}
        </div>
      )}
    </li>
  );
}

export function DocumentacionUploadPanel({ token, documentos }: DocumentacionUploadPanelProps) {
  const queryClient = useQueryClient();
  const [uploadingId, setUploadingId] = useState<string | null>(null);
  const [activeUploads, setActiveUploads] = useState<ActiveDocumentUpload[]>([]);
  const [errorDocId, setErrorDocId] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [uploadVersion, setUploadVersion] = useState(0);
  const [expandedIds, setExpandedIds] = useState<Set<string>>(() => new Set());
  const [accordionInicializado, setAccordionInicializado] = useState(false);

  const uploadMutation = useMutation({
    mutationFn: ({ docId, files }: { docId: string; files: File[] }) =>
      api.subirDocumentoDocumentacion(token, docId, files),
    onSuccess: (_data, variables) => {
      setErrorDocId(null);
      setErrorMessage(null);
      setUploadVersion((v) => v + 1);
      setExpandedIds((prev) => new Set(prev).add(variables.docId));
      void queryClient.invalidateQueries({ queryKey: ['acceso', token] });
    },
    onError: (error, variables) => {
      setErrorDocId(variables.docId);
      setErrorMessage(error instanceof Error ? error.message : 'No se pudo subir el documento.');
    },
    onSettled: (_data, _error, variables) => {
      setUploadingId(null);
      setActiveUploads((prev) => prev.filter((item) => item.docId !== variables.docId));
    },
  });

  const grupos = useMemo(() => {
    const accion: AccesoDocumentacionDocumentoResponse[] = [];
    const revision: AccesoDocumentacionDocumentoResponse[] = [];
    const completados: AccesoDocumentacionDocumentoResponse[] = [];

    for (const doc of documentos) {
      const g = clasificarGrupo(doc);
      if (g === 'accion') accion.push(doc);
      else if (g === 'revision') revision.push(doc);
      else completados.push(doc);
    }

    return { accion, revision, completados };
  }, [documentos]);

  const primerPendienteId =
    grupos.accion.find((d) => d.estado === 'rechazado')?.id ?? grupos.accion[0]?.id ?? null;

  useEffect(() => {
    if (accordionInicializado || documentos.length === 0) return;
    setAccordionInicializado(true);
    if (primerPendienteId) {
      setExpandedIds(new Set([primerPendienteId]));
    }
  }, [accordionInicializado, documentos.length, primerPendienteId]);

  if (documentos.length === 0) {
    return (
      <p className="py-8 text-center text-sm text-muted-foreground">
        No hay documentos pendientes en este momento.
      </p>
    );
  }

  const toggleDoc = (docId: string) => {
    setExpandedIds((prev) => {
      const next = new Set(prev);
      if (next.has(docId)) {
        next.delete(docId);
      } else {
        next.add(docId);
      }
      return next;
    });
  };

  const renderCard = (doc: AccesoDocumentacionDocumentoResponse) => {
    const expanded = expandedIds.has(doc.id) || uploadingId === doc.id;

    return (
      <DocumentoRequisitoCard
        key={doc.id}
        doc={doc}
        uploading={uploadingId === doc.id}
        uploadVersion={uploadVersion}
        errorMessage={errorDocId === doc.id ? errorMessage : null}
        expanded={expanded}
        onToggle={() => toggleDoc(doc.id)}
        onUpload={(files) => {
          setUploadingId(doc.id);
          setExpandedIds((prev) => new Set(prev).add(doc.id));
          setErrorDocId(null);
          setErrorMessage(null);
          setActiveUploads((prev) => [
            ...prev.filter((item) => item.docId !== doc.id),
            {
              docId: doc.id,
              docLabel: doc.nombre,
              fileNames: files.map((file) => file.name),
            },
          ]);
          uploadMutation.mutate({ docId: doc.id, files });
        }}
      />
    );
  };

  return (
    <div className="space-y-6">
      <ActiveDocumentUploadsPanel uploads={activeUploads} />

      <SeccionGrupo
        titulo="Pendiente de su parte"
        contador={grupos.accion.length}
        tono="accion"
        defaultOpen
      >
        {grupos.accion.map(renderCard)}
      </SeccionGrupo>

      <SeccionGrupo
        titulo="En revisión por su abogado"
        contador={grupos.revision.length}
        tono="revision"
        defaultOpen={false}
      >
        {grupos.revision.map(renderCard)}
      </SeccionGrupo>

      <SeccionGrupo
        titulo="Completados"
        contador={grupos.completados.length}
        tono="completados"
        defaultOpen={false}
      >
        {grupos.completados.map(renderCard)}
      </SeccionGrupo>
    </div>
  );
}
