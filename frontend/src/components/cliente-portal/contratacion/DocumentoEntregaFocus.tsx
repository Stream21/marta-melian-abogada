import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { CheckCircle2 } from 'lucide-react';
import { api, type DocumentoRequerido } from '@/api/client';
import { DocumentoArchivoUploadControl } from '@/components/cliente-portal/DocumentoArchivoUploadControl';
import { Button } from '@/components/ui/button';

interface DocumentoEntregaFocusProps {
  token: string;
  documento: DocumentoRequerido;
  onDone: () => void;
}

export function DocumentoEntregaFocus({
  token,
  documento,
  onDone,
}: DocumentoEntregaFocusProps) {
  const queryClient = useQueryClient();
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [uploadVersion, setUploadVersion] = useState(0);
  const entregado = documento.estado === 'entregado' || documento.estado === 'validado';

  const uploadMutation = useMutation({
    mutationFn: (files: File[]) => api.subirDocumentoContratacion(token, documento.id, files),
    onSuccess: () => {
      setErrorMessage(null);
      setUploadVersion((v) => v + 1);
      void queryClient.invalidateQueries({ queryKey: ['acceso', token] }).then(() => {
        onDone();
      });
    },
    onError: (error) => {
      setErrorMessage(error instanceof Error ? error.message : 'No se pudo subir el documento.');
    },
  });

  if (entregado) {
    return (
      <div className="space-y-4 text-center">
        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
          <CheckCircle2 className="h-7 w-7" />
        </div>
        <div>
          <p className="font-semibold text-foreground">{documento.nombre}</p>
          <p className="mt-1 text-sm text-muted-foreground">Documento ya entregado.</p>
        </div>
        <Button className="min-h-[44px] w-full" onClick={onDone}>
          Siguiente
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="rounded-xl border border-border bg-muted/20 p-4">
        <div className="flex flex-wrap items-center gap-2">
          <p className="font-medium text-foreground">
            {documento.nombre}
            {documento.obligatorio && (
              <span className="ml-1 text-amber-700" title="Obligatorio" aria-label="Obligatorio">
                *
              </span>
            )}
          </p>
        </div>
        {documento.descripcion && (
          <p className="mt-2 text-sm text-muted-foreground">{documento.descripcion}</p>
        )}
        <ul className="mt-3 space-y-1.5 text-sm text-muted-foreground">
          <li>
            · <strong className="font-semibold text-foreground">1.</strong> Elija la foto o el
            PDF.
          </li>
          <li>
            · <strong className="font-semibold text-foreground">2.</strong> Pulse{' '}
            <strong className="font-semibold text-foreground">Enviar documento</strong> para
            entregarlo.
          </li>
          <li>· Use buena luz y evite recortes o sombras fuertes.</li>
        </ul>
      </div>

      <DocumentoArchivoUploadControl
        tipo={documento.tipo}
        maxImagenes={documento.maxImagenes}
        uploading={uploadMutation.isPending}
        uploadingTitle="Subiendo documentación…"
        uploadingDescription="Convirtiendo el archivo a PDF para su expediente."
        uploadSuccessKey={`${uploadVersion}-${documento.id}`}
        error={errorMessage}
        readyLabel="Enviar documento"
        onUpload={(files) => {
          setErrorMessage(null);
          uploadMutation.mutate(files);
        }}
      />
    </div>
  );
}
