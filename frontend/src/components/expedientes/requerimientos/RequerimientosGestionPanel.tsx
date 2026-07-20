import { useEffect, useRef, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMercureContratacion } from '@/hooks/useMercureContratacion';
import {
  api,
  openAuthenticatedDocument,
  type RequerimientosDocumentoResponse,
} from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DocumentoArchivoUploadControl } from '@/components/cliente-portal/DocumentoArchivoUploadControl';
import { DocumentoArchivosSubidosList } from '@/components/cliente-portal/DocumentoArchivosSubidosList';
import {
  ActiveDocumentUploadsPanel,
  type ActiveDocumentUpload,
} from '@/components/cliente-portal/ActiveDocumentUploadsPanel';
import { DocumentoLimiteBadge } from '@/components/cliente-portal/DocumentoLimiteBadge';
import { DocumentoPdfGaleria } from '@/components/expedientes/requerimientos/DocumentoPdfGaleria';
import { RequerimientosDocumentoRevisionModal } from './RequerimientosDocumentoRevisionModal';
import { RequerimientosDerivarClienteModal } from './RequerimientosDerivarClienteModal';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { CheckCircle2, Clock, FileText, RotateCcw, UserRound, XCircle } from 'lucide-react';

interface RequerimientosGestionPanelProps {
  expedienteId: string;
  focusDocumentoId?: string;
  abrirRevision?: boolean;
  onFocusConsumed?: () => void;
}

function estadoBadgeVariant(estado: string) {
  switch (estado) {
    case 'validado':
      return 'success' as const;
    case 'entregado':
      return 'warning' as const;
    case 'rechazado':
      return 'destructive' as const;
    default:
      return 'secondary' as const;
  }
}

function estadoIcon(estado: string) {
  switch (estado) {
    case 'validado':
      return CheckCircle2;
    case 'entregado':
      return Clock;
    case 'rechazado':
      return XCircle;
    default:
      return FileText;
  }
}

function puedeAdjuntarAbogado(
  doc: RequerimientosDocumentoResponse,
  reemplazando: boolean,
): boolean {
  if (doc.puedeSubirAbogado === false) return false;
  if (doc.estado === 'validado' && doc.subidoPor === 'abogado') {
    return reemplazando;
  }
  return doc.puedeSubirAbogado === true;
}

function esAportadoPorAbogado(doc: RequerimientosDocumentoResponse): boolean {
  return doc.estado === 'validado' && doc.subidoPor === 'abogado';
}

function esValidadoPorCliente(doc: RequerimientosDocumentoResponse): boolean {
  return doc.estado === 'validado' && doc.subidoPor === 'cliente';
}

function esParcialAbogado(doc: RequerimientosDocumentoResponse): boolean {
  return Boolean(doc.parcialConArchivos && doc.responsableActual === 'abogado');
}

export function RequerimientosGestionPanel({
  expedienteId,
  focusDocumentoId,
  abrirRevision,
  onFocusConsumed,
}: RequerimientosGestionPanelProps) {
  const queryClient = useQueryClient();
  useMercureContratacion(expedienteId);
  const focusHandled = useRef<string | null>(null);

  const [modalDoc, setModalDoc] = useState<RequerimientosDocumentoResponse | null>(null);
  const [modalModo, setModalModo] = useState<'revision' | 'devolucion'>('revision');
  const [galeriaDoc, setGaleriaDoc] = useState<RequerimientosDocumentoResponse | null>(null);
  const [uploadingId, setUploadingId] = useState<string | null>(null);
  const [activeUploads, setActiveUploads] = useState<ActiveDocumentUpload[]>([]);
  const [abriendoPdfId, setAbriendoPdfId] = useState<string | null>(null);
  const [uploadVersion, setUploadVersion] = useState(0);
  const [reemplazandoDocId, setReemplazandoDocId] = useState<string | null>(null);
  const [confirmacionSubidaId, setConfirmacionSubidaId] = useState<string | null>(null);
  const [derivarDoc, setDerivarDoc] = useState<RequerimientosDocumentoResponse | null>(null);
  const [modoSubidaPorDoc, setModoSubidaPorDoc] = useState<Record<string, 'validar' | 'aportar'>>({});

  const { data, isLoading, error } = useQuery({
    queryKey: ['requerimientos', expedienteId],
    queryFn: () => api.getRequerimientos(expedienteId),
    refetchInterval: 8000,
  });

  const validarMutation = useMutation({
    mutationFn: (docId: string) => api.validarDocumentoRequerimientos(expedienteId, docId),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['requerimientos', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
      setModalDoc(null);
      setModalModo('revision');
    },
  });

  const devolverMutation = useMutation({
    mutationFn: ({ docId, nota }: { docId: string; nota: string }) =>
      api.devolverDocumentoRequerimientos(expedienteId, docId, nota),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['requerimientos', expedienteId] });
      setModalDoc(null);
      setModalModo('revision');
    },
  });

  const subirAbogadoMutation = useMutation({
    mutationFn: ({
      docId,
      files,
      modo,
    }: {
      docId: string;
      files: File[];
      modo: 'validar' | 'aportar';
    }) => api.subirDocumentoRequerimientosAbogado(expedienteId, docId, files, modo),
    onSuccess: (_data, variables) => {
      void queryClient.invalidateQueries({ queryKey: ['requerimientos', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
      setUploadVersion((v) => v + 1);
      if (variables.modo === 'validar') {
        setConfirmacionSubidaId(variables.docId);
      }
      setReemplazandoDocId(null);
    },
    onSettled: (_data, _error, variables) => {
      setUploadingId(null);
      setActiveUploads((prev) => prev.filter((item) => item.docId !== variables.docId));
    },
  });

  const asignarAbogadoMutation = useMutation({
    mutationFn: (docId: string) => api.asignarDocumentoRequerimientoAbogado(expedienteId, docId),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['requerimientos', expedienteId] });
    },
  });

  const derivarClienteMutation = useMutation({
    mutationFn: ({ docId, nota }: { docId: string; nota: string }) =>
      api.derivarDocumentoRequerimientoCliente(expedienteId, docId, nota),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['requerimientos', expedienteId] });
      setDerivarDoc(null);
    },
  });

  useEffect(() => {
    if (!data || !focusDocumentoId || focusHandled.current === focusDocumentoId) return;

    const doc = data.documentos.find((d) => d.id === focusDocumentoId);
    const element = document.getElementById(`doc-req-${focusDocumentoId}`);

    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'center' });
      element.classList.add('ring-2', 'ring-primary', 'ring-offset-2');
      window.setTimeout(() => {
        element.classList.remove('ring-2', 'ring-primary', 'ring-offset-2');
      }, 2500);
    }

    if (abrirRevision && doc?.puedeValidarAbogado) {
      setModalDoc(doc);
      setModalModo('revision');
    }

    focusHandled.current = focusDocumentoId;
    onFocusConsumed?.();
  }, [data, focusDocumentoId, abrirRevision, onFocusConsumed]);

  if (isLoading) {
    return <p className="text-muted-foreground py-8 text-center">Cargando requerimientos…</p>;
  }

  if (error || !data) {
    return (
      <p className="text-destructive py-8 text-center">
        No se pudo cargar la fase de requerimientos.
      </p>
    );
  }

  const listo = data.progreso.requerimientosListo;

  const abrirDocumento = async (doc: RequerimientosDocumentoResponse) => {
    const archivos = doc.archivos ?? [];
    if (archivos.length > 1) {
      setGaleriaDoc(doc);
      return;
    }

    setAbriendoPdfId(doc.id);
    try {
      const archivoId = archivos[0]?.id;
      await openAuthenticatedDocument(
        api.requerimientosDocumentoArchivoUrl(expedienteId, doc.id, archivoId),
      );
    } catch (e) {
      window.alert(e instanceof Error ? e.message : 'No se pudo abrir el documento.');
    } finally {
      setAbriendoPdfId(null);
    }
  };

  return (
    <div className="space-y-6">
      <div className="panel p-6">
        <div className="flex flex-wrap items-start justify-between gap-4 mb-6">
          <div>
            <p className="section-label">Fase 2</p>
            <h2 className="panel-title">Requerimientos documentales</h2>
            <p className="text-sm text-muted-foreground mt-1">
              Valide los documentos del cliente o adjúntelos usted directamente. Cada archivo subido se
              guarda como PDF independiente.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Badge variant={listo ? 'success' : 'warning'}>
              {listo ? 'Listo para presentación' : 'En progreso'}
            </Badge>
            <Badge variant="info">
              {data.progreso.validados}/{data.progreso.obligatorios} obligatorios validados
            </Badge>
            {data.progreso.enRevision > 0 && (
              <Badge variant="warning">{data.progreso.enRevision} en revisión</Badge>
            )}
            {data.agenteResponsableExpediente === 'cliente' && (
              <Badge variant="secondary">Pendiente del cliente</Badge>
            )}
            {data.agenteResponsableExpediente === 'abogado' && (
              <Badge variant="info">Acción del abogado</Badge>
            )}
          </div>
        </div>

        <ActiveDocumentUploadsPanel uploads={activeUploads} className="mb-4" />

        <div className="grid gap-3">
          {data.documentos.length === 0 ? (
            <p className="text-sm text-muted-foreground italic py-4 text-center">
              No hay documentos configurados. Añada uno o revise la plantilla del trámite.
            </p>
          ) : (
            data.documentos.map((doc) => {
              const Icon = estadoIcon(doc.estado);
              const mostrarRevision = doc.puedeValidarAbogado === true;
              const mostrarDevolucionValidado = esValidadoPorCliente(doc) && doc.puedeDevolverAbogado === true;
              const mostrarPdf = doc.tieneArchivo && !mostrarRevision;
              const aportadoPorAbogado = esAportadoPorAbogado(doc);
              const parcialAbogado = esParcialAbogado(doc);
              const mostrarSubida = puedeAdjuntarAbogado(doc, reemplazandoDocId === doc.id);
              const modoSubida = modoSubidaPorDoc[doc.id] ?? (doc.tipo === 'conjunto' ? 'aportar' : 'validar');
              const mostrarConfirmacionSubida = confirmacionSubidaId === doc.id && aportadoPorAbogado;
              const archivos = doc.archivos ?? [];
              const mostrarPdfParcial = parcialAbogado && archivos.length > 0;

              return (
                <div
                  key={doc.id}
                  id={`doc-req-${doc.id}`}
                  className={cn(
                    'rounded-xl border p-4 transition-shadow scroll-mt-24',
                    doc.estado === 'validado' && 'border-emerald-200 bg-emerald-50/40',
                    doc.estado === 'entregado' && 'border-amber-200 bg-amber-50/30',
                    doc.estado === 'rechazado' && 'border-red-200 bg-red-50/30',
                  )}
                >
                  <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="min-w-0 flex-1 space-y-2">
                      <div className="flex flex-wrap items-center gap-2">
                        <Icon className="h-4 w-4 shrink-0 text-muted-foreground" />
                        <span className="font-medium">{doc.nombre}</span>
                        {doc.obligatorio && <Badge variant="secondary">Obligatorio</Badge>}
                        <Badge variant={estadoBadgeVariant(doc.estado)}>{doc.estadoLabel}</Badge>
                        {doc.responsableActual === 'abogado' && doc.estado !== 'validado' && (
                          <Badge variant="info">A cargo del abogado</Badge>
                        )}
                        {doc.responsableActual === 'cliente' &&
                          (doc.estado === 'pendiente' || doc.estado === 'rechazado') && (
                            <Badge variant="secondary">Pendiente del cliente</Badge>
                          )}
                        <Badge variant="outline">{doc.origenLabel}</Badge>
                        <DocumentoLimiteBadge tipo={doc.tipo} maxImagenes={doc.maxImagenes} />
                      </div>
                      {doc.descripcion && (
                        <p className="text-sm text-muted-foreground">{doc.descripcion}</p>
                      )}
                      {archivos.length > 0 && (
                        <DocumentoArchivosSubidosList archivos={archivos} className="mt-2" />
                      )}
                      {doc.estado === 'entregado' && doc.subidoPor === 'cliente' && (
                        <p className="text-xs text-amber-800">
                          Enviado por el cliente. Debe validarlo o devolverlo antes de que quede listo.
                        </p>
                      )}
                      {esValidadoPorCliente(doc) && (
                        <p className="text-xs text-emerald-800">
                          Validado tras revisión del cliente. Puede devolverlo si detecta un error.
                        </p>
                      )}
                      {parcialAbogado && (
                        <p className="text-xs text-blue-800">
                          Ha aportado archivos parciales. Derive al cliente cuando quiera que complete
                          el requisito.
                        </p>
                      )}
                      {esAportadoPorAbogado(doc) && (
                        <p className="text-xs text-emerald-800">
                          Aportado directamente por el abogado, sin paso de revisión intermedio.
                        </p>
                      )}
                      {doc.notaRechazo && (
                        <p className="text-xs text-red-700 bg-red-50 rounded p-2 border border-red-100">
                          Devuelto: {doc.notaRechazo}
                        </p>
                      )}
                    </div>

                    <div className="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col lg:items-stretch">
                      {doc.puedeTomarAbogado && (
                        <Button
                          variant="outline"
                          size="sm"
                          className="min-w-[120px]"
                          disabled={asignarAbogadoMutation.isPending}
                          onClick={() => asignarAbogadoMutation.mutate(doc.id)}
                        >
                          Tomar requisito
                        </Button>
                      )}
                      {doc.puedeDerivarCliente && (
                        <Button
                          variant="outline"
                          size="sm"
                          className="min-w-[120px] border-primary/30 text-primary hover:bg-primary/5"
                          onClick={() => setDerivarDoc(doc)}
                        >
                          <UserRound className="mr-2 h-4 w-4" />
                          Derivar al cliente
                        </Button>
                      )}
                      {mostrarRevision && (
                        <Button
                          size="sm"
                          className="min-w-[120px]"
                          onClick={() => {
                            setModalModo('revision');
                            setModalDoc(doc);
                          }}
                        >
                          Revisar y validar
                        </Button>
                      )}
                      {mostrarDevolucionValidado && (
                        <Button
                          variant="outline"
                          size="sm"
                          className="min-w-[120px] border-amber-300 text-amber-800 hover:bg-amber-50"
                          onClick={() => {
                            setModalModo('devolucion');
                            setModalDoc(doc);
                          }}
                        >
                          Devolver al cliente
                        </Button>
                      )}
                      {mostrarPdf && (
                        <Button
                          variant="outline"
                          size="sm"
                          className="min-w-[120px]"
                          disabled={abriendoPdfId === doc.id}
                          onClick={() => void abrirDocumento(doc)}
                        >
                          {abriendoPdfId === doc.id
                            ? 'Abriendo…'
                            : archivos.length > 1
                              ? `Ver ${archivos.length} archivos`
                              : 'Ver documento'}
                        </Button>
                      )}
                      {mostrarPdfParcial && !mostrarPdf && (
                        <Button
                          variant="outline"
                          size="sm"
                          className="min-w-[120px]"
                          disabled={abriendoPdfId === doc.id}
                          onClick={() => void abrirDocumento(doc)}
                        >
                          Ver archivos aportados
                        </Button>
                      )}
                    </div>
                  </div>

                  {(mostrarConfirmacionSubida || aportadoPorAbogado || parcialAbogado || mostrarSubida) && (
                    <div className="mt-4 border-t border-border/60 pt-4 space-y-3">
                      {mostrarConfirmacionSubida && (
                        <div className="flex gap-2 rounded-lg border border-emerald-200 bg-emerald-50/80 p-3 text-sm text-emerald-900">
                          <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                          <p>
                            Documento adjuntado correctamente. Puede ver los PDF o volver a subir si
                            necesita corregirlo.
                          </p>
                        </div>
                      )}

                      {aportadoPorAbogado && !mostrarSubida && (
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            setReemplazandoDocId(doc.id);
                            setConfirmacionSubidaId(null);
                          }}
                        >
                          <RotateCcw className="mr-2 h-4 w-4" />
                          Volver a subir otro archivo
                        </Button>
                      )}

                      {mostrarSubida && (
                        <>
                          <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="text-xs font-medium text-muted-foreground">
                              {aportadoPorAbogado
                                ? 'Sustituir el documento adjuntado'
                                : parcialAbogado
                                  ? 'Añadir más archivos antes de derivar'
                                  : 'Adjuntar en nombre del cliente'}
                            </p>
                            {aportadoPorAbogado && (
                              <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => setReemplazandoDocId(null)}
                              >
                                Cancelar
                              </Button>
                            )}
                          </div>
                          {!aportadoPorAbogado && (
                            <div className="flex flex-wrap gap-2">
                              <Button
                                type="button"
                                size="sm"
                                variant={modoSubida === 'validar' ? 'default' : 'outline'}
                                onClick={() =>
                                  setModoSubidaPorDoc((prev) => ({ ...prev, [doc.id]: 'validar' }))
                                }
                              >
                                Validar y cerrar
                              </Button>
                              <Button
                                type="button"
                                size="sm"
                                variant={modoSubida === 'aportar' ? 'default' : 'outline'}
                                onClick={() =>
                                  setModoSubidaPorDoc((prev) => ({ ...prev, [doc.id]: 'aportar' }))
                                }
                              >
                                Aportar y derivar después
                              </Button>
                            </div>
                          )}
                          <DocumentoArchivoUploadControl
                            tipo={doc.tipo}
                            maxImagenes={doc.maxImagenes}
                            uploading={uploadingId === doc.id}
                            showProgressOverlay={false}
                            suppressUploadingUi={uploadingId === doc.id}
                            uploadingTitle="Adjuntando al expediente…"
                            uploadingDescription="Convirtiendo cada archivo a PDF por separado. Con imágenes grandes puede tardar un poco."
                            uploadSuccessKey={`${uploadVersion}-${doc.id}`}
                            error={
                              subirAbogadoMutation.error && uploadingId === null
                                ? subirAbogadoMutation.error.message
                                : null
                            }
                            readyLabel="Listo — adjuntar documento"
                            showLimiteHeader={false}
                            onUpload={(files) => {
                              setUploadingId(doc.id);
                              setActiveUploads((prev) => [
                                ...prev.filter((item) => item.docId !== doc.id),
                                {
                                  docId: doc.id,
                                  docLabel: doc.nombre,
                                  fileNames: files.map((file) => file.name),
                                },
                              ]);
                              subirAbogadoMutation.mutate({
                                docId: doc.id,
                                files,
                                modo: aportadoPorAbogado ? 'validar' : modoSubida,
                              });
                            }}
                          />
                        </>
                      )}
                    </div>
                  )}
                </div>
              );
            })
          )}
        </div>
      </div>

      <RequerimientosDerivarClienteModal
        doc={derivarDoc}
        open={derivarDoc !== null}
        onClose={() => setDerivarDoc(null)}
        onConfirm={(docId, nota) => derivarClienteMutation.mutate({ docId, nota })}
        pending={derivarClienteMutation.isPending}
        error={derivarClienteMutation.error?.message ?? null}
      />

      <RequerimientosDocumentoRevisionModal
        doc={modalDoc}
        open={modalDoc !== null}
        onClose={() => {
          setModalDoc(null);
          setModalModo('revision');
        }}
        buildArchivoUrl={(archivoId) =>
          api.requerimientosDocumentoArchivoUrl(expedienteId, modalDoc!.id, archivoId)
        }
        modo={modalModo}
        onValidar={(docId) => validarMutation.mutate(docId)}
        onDevolver={(docId, nota) => devolverMutation.mutate({ docId, nota })}
        validando={validarMutation.isPending}
        devolviendo={devolverMutation.isPending}
        errorAccion={validarMutation.error?.message ?? devolverMutation.error?.message ?? null}
      />

      <Dialog open={galeriaDoc !== null} onOpenChange={(open) => !open && setGaleriaDoc(null)}>
        <DialogContent className="max-h-[92vh] max-w-5xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{galeriaDoc?.nombre}</DialogTitle>
          </DialogHeader>
          {galeriaDoc && (galeriaDoc.archivos ?? []).length > 0 && (
            <DocumentoPdfGaleria
              archivos={galeriaDoc.archivos ?? []}
              buildUrl={(archivoId) =>
                api.requerimientosDocumentoArchivoUrl(expedienteId, galeriaDoc.id, archivoId)
              }
              title={galeriaDoc.nombre}
            />
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
