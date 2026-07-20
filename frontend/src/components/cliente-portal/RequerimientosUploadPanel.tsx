import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { AlertTriangle, CheckCircle2, Clock } from 'lucide-react';
import { api, type AccesoRequerimientosDocumentoResponse } from '@/api/client';
import { DocumentoArchivoUploadControl } from '@/components/cliente-portal/DocumentoArchivoUploadControl';
import {
  ActiveDocumentUploadsPanel,
  type ActiveDocumentUpload,
} from '@/components/cliente-portal/ActiveDocumentUploadsPanel';
import { DocumentoArchivosSubidosList } from '@/components/cliente-portal/DocumentoArchivosSubidosList';
import { DocumentoLimiteBadge } from '@/components/cliente-portal/DocumentoLimiteBadge';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

interface RequerimientosUploadPanelProps {
  token: string;
  documentos: AccesoRequerimientosDocumentoResponse[];
}

function estadoLabel(doc: AccesoRequerimientosDocumentoResponse) {
  if (doc.estadoLabel) return doc.estadoLabel;
  switch (doc.estado) {
    case 'validado':
      return doc.subidoPor === 'abogado' ? 'Aportado por su abogado' : 'Validado';
    case 'entregado':
      return 'En revisión';
    case 'rechazado':
      return 'Devuelto — debe volver a subir';
    default:
      return 'Pendiente de entrega';
  }
}

export function RequerimientosUploadPanel({ token, documentos }: RequerimientosUploadPanelProps) {
  const queryClient = useQueryClient();
  const [uploadingId, setUploadingId] = useState<string | null>(null);
  const [activeUploads, setActiveUploads] = useState<ActiveDocumentUpload[]>([]);
  const [errorDocId, setErrorDocId] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [uploadVersion, setUploadVersion] = useState(0);
  const [confirmacionEnvioId, setConfirmacionEnvioId] = useState<string | null>(null);

  const uploadMutation = useMutation({
    mutationFn: ({ docId, files }: { docId: string; files: File[] }) =>
      api.subirDocumentoRequerimientos(token, docId, files),
    onSuccess: (_data, variables) => {
      setErrorDocId(null);
      setErrorMessage(null);
      setUploadVersion((v) => v + 1);
      setConfirmacionEnvioId(variables.docId);
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

  if (documentos.length === 0) {
    return (
      <p className="text-sm text-muted-foreground italic text-center py-4">
        No hay documentos pendientes en este momento.
      </p>
    );
  }

  return (
    <div className="space-y-4">
      <ActiveDocumentUploadsPanel uploads={activeUploads} />
      <ul className="space-y-3">
      {documentos.map((doc) => {
        const enRevision = doc.estado === 'entregado';
        const validado = doc.estado === 'validado';
        const rechazado = doc.estado === 'rechazado';
        const aportadoPorAbogado = validado && doc.subidoPor === 'abogado';
        const validadoTrasRevision = validado && doc.subidoPor !== 'abogado';
        const gestionadoPorAbogado =
          doc.responsableActual === 'abogado' &&
          doc.estado === 'pendiente' &&
          !doc.puedeSubir;
        const derivadoAlCliente =
          doc.responsableActual === 'cliente' &&
          doc.parcialConArchivos &&
          doc.estado === 'pendiente';
        const puedeSubir = doc.puedeSubir;
        const mostrarConfirmacionEnvio = confirmacionEnvioId === doc.id && enRevision;

        return (
          <li
            key={doc.id}
            className={cn(
              'rounded-lg border p-4 text-sm',
              validado && 'border-emerald-200 bg-emerald-50/40',
              enRevision && 'border-amber-200 bg-amber-50/30',
              rechazado && 'border-red-200 bg-red-50/30',
            )}
          >
            <div className="space-y-3">
              <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                  <span className="font-medium">{doc.nombre}</span>
                  {doc.obligatorio && <Badge variant="secondary">Obligatorio</Badge>}
                  <DocumentoLimiteBadge tipo={doc.tipo} maxImagenes={doc.maxImagenes} />
                  <Badge
                    variant={
                      validado ? 'success' : enRevision ? 'warning' : rechazado ? 'destructive' : 'secondary'
                    }
                  >
                    {validado && <CheckCircle2 className="mr-1 h-3 w-3" />}
                    {enRevision && <Clock className="mr-1 h-3 w-3" />}
                    {estadoLabel(doc)}
                  </Badge>
                </div>
                {doc.descripcion && (
                  <p className="mt-1 text-muted-foreground">{doc.descripcion}</p>
                )}
                {(doc.archivos ?? []).length > 0 && (
                  <DocumentoArchivosSubidosList
                    archivos={doc.archivos ?? []}
                    className="mt-2"
                  />
                )}
                {puedeSubir && (
                  <p className="mt-2 text-xs text-muted-foreground">
                    {rechazado
                      ? 'Corrija el documento según la nota de su abogado y pulse «Listo» para reenviarlo.'
                      : 'Elija los archivos, revíselos y pulse «Listo» para enviarlos a revisión.'}
                  </p>
                )}
                {mostrarConfirmacionEnvio && (
                  <div className="mt-3 flex gap-2 rounded-lg border border-emerald-200 bg-emerald-50/80 p-3 text-xs text-emerald-900">
                    <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                    <div>
                      <p className="font-semibold">Documento enviado correctamente</p>
                      <p className="mt-1">
                        Ha pasado a revisión por su abogado. No podrá modificarlo hasta que lo valide o
                        lo devuelva con indicaciones.
                      </p>
                    </div>
                  </div>
                )}
                {enRevision && !mostrarConfirmacionEnvio && (
                  <p className="mt-2 text-xs text-amber-800">
                    En revisión por su abogado. No podrá modificarlo hasta que lo valide o lo devuelva.
                  </p>
                )}
                {derivadoAlCliente && (
                  <div className="mt-3 flex gap-2 rounded-lg border border-primary/20 bg-primary/5 p-3 text-xs text-foreground">
                    <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                    <p>
                      Su abogado ha aportado parte de la documentación y le solicita que complete este
                      requisito. Puede añadir los archivos que falten.
                    </p>
                  </div>
                )}
                {gestionadoPorAbogado && (
                  <div className="mt-3 flex gap-2 rounded-lg border border-muted bg-muted/40 p-3 text-xs text-muted-foreground">
                    <Clock className="mt-0.5 h-4 w-4 shrink-0" />
                    <p>Su abogado está gestionando este documento. No necesita subir nada por ahora.</p>
                  </div>
                )}
                {aportadoPorAbogado && (
                  <div className="mt-3 flex gap-2 rounded-lg border border-emerald-200 bg-emerald-50/80 p-3 text-xs text-emerald-900">
                    <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                    <p>Su abogado ha aportado este documento. No necesita hacer nada más.</p>
                  </div>
                )}
                {validadoTrasRevision && (
                  <div className="mt-3 flex gap-2 rounded-lg border border-emerald-200 bg-emerald-50/80 p-3 text-xs text-emerald-900">
                    <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                    <p>Su abogado ha validado este documento. Ya no necesita volver a subirlo.</p>
                  </div>
                )}
                {rechazado && doc.notaRechazo && (
                  <div className="mt-3 flex gap-2 rounded-md border border-red-200 bg-red-50 p-3 text-xs text-red-800">
                    <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                    <div>
                      <p className="font-semibold">Motivo de la devolución</p>
                      <p className="mt-1 whitespace-pre-wrap">{doc.notaRechazo}</p>
                    </div>
                  </div>
                )}
              </div>

              {puedeSubir && (
                <DocumentoArchivoUploadControl
                  tipo={doc.tipo}
                  maxImagenes={doc.maxImagenes}
                  uploading={uploadingId === doc.id}
                  showProgressOverlay={false}
                  suppressUploadingUi={uploadingId === doc.id}
                  uploadingTitle="Enviando a su abogado…"
                  uploadingDescription="Convirtiendo sus archivos a PDF. No cierre esta página; puede tardar un poco con imágenes grandes."
                  uploadSuccessKey={`${uploadVersion}-${doc.id}-${doc.estado}`}
                  error={errorDocId === doc.id ? errorMessage : null}
                  variant={rechazado ? 'default' : 'outline'}
                  readyLabel="Listo — enviar a mi abogado"
                  onUpload={(files) => {
                    setUploadingId(doc.id);
                    setErrorDocId(null);
                    setErrorMessage(null);
                    setConfirmacionEnvioId(null);
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
              )}
            </div>
          </li>
        );
      })}
      </ul>
    </div>
  );
}
