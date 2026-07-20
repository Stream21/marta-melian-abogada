import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { CheckCircle2 } from 'lucide-react';
import { api, type DocumentoRequerido } from '@/api/client';
import { DocumentoArchivoUploadControl } from '@/components/cliente-portal/DocumentoArchivoUploadControl';
import { DocumentoLimiteBadge } from '@/components/cliente-portal/DocumentoLimiteBadge';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

interface DocumentoUploadPanelProps {
  token: string;
  documentos: DocumentoRequerido[];
}

export function DocumentoUploadPanel({ token, documentos }: DocumentoUploadPanelProps) {
  const queryClient = useQueryClient();
  const [uploadingId, setUploadingId] = useState<string | null>(null);
  const [errorDocId, setErrorDocId] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [uploadVersion, setUploadVersion] = useState(0);

  const uploadMutation = useMutation({
    mutationFn: ({ docId, files }: { docId: string; files: File[] }) =>
      api.subirDocumentoContratacion(token, docId, files),
    onSuccess: () => {
      setErrorDocId(null);
      setErrorMessage(null);
      setUploadVersion((v) => v + 1);
      void queryClient.invalidateQueries({ queryKey: ['acceso', token] });
    },
    onError: (error, variables) => {
      setErrorDocId(variables.docId);
      setErrorMessage(error instanceof Error ? error.message : 'No se pudo subir el documento.');
    },
    onSettled: () => setUploadingId(null),
  });

  if (documentos.length === 0) {
    return (
      <p className="text-sm text-muted-foreground italic">
        No hay documentación adicional configurada para este trámite.
      </p>
    );
  }

  return (
    <ul className="space-y-3">
      {documentos.map((doc) => {
        const entregado = doc.estado === 'entregado' || doc.estado === 'validado';

        return (
          <li
            key={doc.id}
            className={cn(
              'rounded-lg border p-4 text-sm',
              entregado ? 'border-emerald-200 bg-emerald-50/40' : 'border-border bg-card',
            )}
          >
            <div className="flex items-start justify-between gap-3">
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-medium">{doc.nombre}</span>
                  {doc.obligatorio && <Badge variant="secondary">Obligatorio</Badge>}
                  <DocumentoLimiteBadge tipo={doc.tipo} maxImagenes={doc.maxImagenes} />
                  {entregado && (
                    <Badge variant="success" className="gap-1">
                      <CheckCircle2 className="h-3 w-3" />
                      Subido
                    </Badge>
                  )}
                </div>
                {doc.descripcion && <p className="mt-1 text-muted-foreground">{doc.descripcion}</p>}
              </div>
            </div>

            {!entregado && (
              <DocumentoArchivoUploadControl
                tipo={doc.tipo}
                maxImagenes={doc.maxImagenes}
                uploading={uploadingId === doc.id}
                uploadingTitle="Subiendo documentación…"
                uploadingDescription="Convirtiendo el archivo a PDF para su expediente."
                uploadSuccessKey={`${uploadVersion}-${doc.id}`}
                error={errorDocId === doc.id ? errorMessage : null}
                readyLabel="Listo — enviar documento"
                onUpload={(files) => {
                  setUploadingId(doc.id);
                  setErrorDocId(null);
                  setErrorMessage(null);
                  uploadMutation.mutate({ docId: doc.id, files });
                }}
              />
            )}
          </li>
        );
      })}
    </ul>
  );
}
