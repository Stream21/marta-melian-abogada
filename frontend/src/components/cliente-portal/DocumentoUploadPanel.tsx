import { useRef, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { CheckCircle2, Upload } from 'lucide-react';
import { api, type DocumentoRequerido } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface DocumentoUploadPanelProps {
  token: string;
  documentos: DocumentoRequerido[];
}

export function DocumentoUploadPanel({ token, documentos }: DocumentoUploadPanelProps) {
  const queryClient = useQueryClient();
  const inputRefs = useRef<Record<string, HTMLInputElement | null>>({});
  const [uploadingId, setUploadingId] = useState<string | null>(null);

  const uploadMutation = useMutation({
    mutationFn: ({ docId, file }: { docId: string; file: File }) =>
      api.subirDocumentoContratacion(token, docId, file),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['acceso', token] }),
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
              <>
                <input
                  ref={(el) => {
                    inputRefs.current[doc.id] = el;
                  }}
                  type="file"
                  accept="image/*,application/pdf"
                  className="hidden"
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (!file) return;
                    setUploadingId(doc.id);
                    uploadMutation.mutate({ docId: doc.id, file });
                    e.target.value = '';
                  }}
                />
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  className="mt-3"
                  disabled={uploadingId === doc.id}
                  onClick={() => inputRefs.current[doc.id]?.click()}
                >
                  <Upload className="mr-2 h-4 w-4" />
                  {uploadingId === doc.id ? 'Subiendo…' : 'Subir documento'}
                </Button>
              </>
            )}
          </li>
        );
      })}
    </ul>
  );
}
