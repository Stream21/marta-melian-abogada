import { useEffect, useRef, useState } from 'react';
import { Check, FileText, Loader2, Plus, Upload, X } from 'lucide-react';
import type { TipoDocumentoRequerido } from '@/api/client';
import { Button } from '@/components/ui/button';
import { FileUploadProgressPanel } from '@/components/cliente-portal/FileUploadProgressPanel';
import {
  documentoUploadLimiteDetalle,
  documentoUploadLimiteLabel,
  DOCUMENTO_UPLOAD_ACCEPT,
  esDocumentoConjunto,
} from '@/lib/documento-upload-limite';
import { cn } from '@/lib/utils';

const ACCEPT = DOCUMENTO_UPLOAD_ACCEPT;

interface DocumentoArchivoUploadControlProps {
  tipo: TipoDocumentoRequerido | string;
  maxImagenes: number;
  uploading?: boolean;
  error?: string | null;
  submitLabel?: string;
  readyLabel?: string;
  onUpload: (files: File[]) => void;
  variant?: 'default' | 'outline';
  className?: string;
  /** Oculta el texto de ayuda si el padre ya muestra el límite */
  showLimiteHeader?: boolean;
  uploadSuccessKey?: string | number;
  /** Texto del panel de progreso durante la subida */
  uploadingTitle?: string;
  uploadingDescription?: string;
  /** Si false, no muestra el overlay local (p. ej. cuando el padre ya tiene ActiveDocumentUploadsPanel) */
  showProgressOverlay?: boolean;
  /** Si true, oculta spinner del botón mientras sube (p. ej. panel global activo) */
  suppressUploadingUi?: boolean;
}

export function DocumentoArchivoUploadControl({
  tipo,
  maxImagenes,
  uploading = false,
  error,
  submitLabel = 'Subir documento',
  readyLabel = 'Listo — enviar documento',
  onUpload,
  variant = 'outline',
  className,
  showLimiteHeader = true,
  uploadSuccessKey,
  uploadingTitle,
  uploadingDescription,
  showProgressOverlay = true,
  suppressUploadingUi = false,
}: DocumentoArchivoUploadControlProps) {
  const inputRef = useRef<HTMLInputElement | null>(null);
  const [seleccionados, setSeleccionados] = useState<File[]>([]);
  const [errorLocal, setErrorLocal] = useState<string | null>(null);
  const multiple = esDocumentoConjunto(tipo, maxImagenes);
  const limite = multiple ? maxImagenes : 1;

  useEffect(() => {
    setSeleccionados([]);
    setErrorLocal(null);
  }, [uploadSuccessKey]);

  const agregarArchivos = (incoming: FileList | File[] | null) => {
    if (!incoming || uploading) return;
    const nuevos = Array.from(incoming);
    if (nuevos.length === 0) return;

    setErrorLocal(null);

    if (!multiple) {
      setSeleccionados([nuevos[0]]);
      return;
    }

    setSeleccionados((prev) => {
      const combinados = [...prev, ...nuevos];
      if (combinados.length > limite) {
        setErrorLocal(`Puede añadir como máximo ${limite} archivo(s) para este requisito.`);
        return combinados.slice(0, limite);
      }
      return combinados;
    });
  };

  const quitarArchivo = (index: number) => {
    setSeleccionados((prev) => prev.filter((_, i) => i !== index));
    setErrorLocal(null);
  };

  const enviar = () => {
    if (seleccionados.length === 0) {
      setErrorLocal('Seleccione al menos un archivo antes de pulsar Listo.');
      return;
    }
    onUpload(seleccionados);
  };

  const errorVisible = error ?? errorLocal;
  const puedeEnviar = seleccionados.length > 0 && !uploading;
  const haySeleccion = seleccionados.length > 0;
  const puedeAnadir = multiple
    ? seleccionados.length < limite && seleccionados.length === 0
    : seleccionados.length === 0;

  const etiquetaEnvio =
    seleccionados.length > 1
      ? `Confirmar y enviar ${seleccionados.length} archivos`
      : readyLabel || submitLabel;

  return (
    <div className={cn('relative space-y-3', className)}>
      {uploading && showProgressOverlay && (
        <div className="absolute inset-0 z-10 flex min-h-[140px] items-center justify-center rounded-lg bg-background/90 backdrop-blur-sm">
          <FileUploadProgressPanel
            fileCount={Math.max(seleccionados.length, 1)}
            fileNames={seleccionados.map((file) => file.name)}
            title={uploadingTitle}
            description={uploadingDescription}
          />
        </div>
      )}

      {showLimiteHeader && (
        <div className="rounded-md border border-dashed border-border/80 bg-muted/20 px-3 py-2">
          <p className="text-xs font-medium text-foreground">
            {documentoUploadLimiteLabel(tipo, maxImagenes)}
          </p>
          <p className="mt-0.5 text-xs text-muted-foreground">
            {documentoUploadLimiteDetalle(tipo, maxImagenes)}
          </p>
        </div>
      )}

      <input
        ref={inputRef}
        type="file"
        accept={ACCEPT}
        multiple={multiple}
        className="hidden"
        onChange={(e) => {
          agregarArchivos(e.target.files);
          e.target.value = '';
        }}
      />

      {puedeAnadir && (
        <Button
          type="button"
          variant={variant}
          size="sm"
          disabled={uploading}
          className="w-full sm:w-auto"
          onClick={() => inputRef.current?.click()}
        >
          {multiple ? (
            <>
              <Plus className="mr-2 h-4 w-4" />
              {seleccionados.length === 0 ? 'Añadir archivo' : 'Añadir otro archivo'}
            </>
          ) : (
            <>
              <Upload className="mr-2 h-4 w-4" />
              Elegir archivo
            </>
          )}
        </Button>
      )}

      {haySeleccion && (
        <div className="overflow-hidden rounded-xl border border-primary/20 bg-card">
          <div className="border-b border-border/70 bg-primary/5 px-3 py-2">
            <p className="text-sm font-medium text-foreground">
              {seleccionados.length === 1
                ? 'Archivo listo para enviar'
                : `${seleccionados.length} archivos listos para enviar`}
            </p>
            <p className="mt-0.5 text-xs text-muted-foreground">
              Revise el contenido. Tras confirmar no podrá cambiarlo hasta que su abogado lo revise.
            </p>
          </div>

          <ul className="divide-y divide-border">
            {seleccionados.map((file, index) => (
              <li
                key={`${file.name}-${index}`}
                className="flex items-center gap-3 px-3 py-2.5"
              >
                <FileText className="h-4 w-4 shrink-0 text-primary" />
                <span className="min-w-0 flex-1 truncate text-sm text-foreground">
                  {file.name}
                </span>
                <button
                  type="button"
                  disabled={uploading}
                  onClick={() => quitarArchivo(index)}
                  className={cn(
                    'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full',
                    'border border-red-200 bg-red-50 text-red-600 transition-colors',
                    'hover:bg-red-100 hover:text-red-700',
                    'disabled:pointer-events-none disabled:opacity-50',
                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400',
                  )}
                  aria-label={`Quitar ${file.name}`}
                  title="Quitar archivo"
                >
                  <X className="h-4 w-4" strokeWidth={2.5} />
                </button>
              </li>
            ))}
          </ul>

          {multiple && seleccionados.length < limite && (
            <div className="border-t border-border px-3 py-2">
              <button
                type="button"
                disabled={uploading}
                onClick={() => inputRef.current?.click()}
                className="text-sm font-medium text-primary hover:underline disabled:opacity-50"
              >
                + Añadir otro archivo ({seleccionados.length}/{limite})
              </button>
            </div>
          )}

          <div className="border-t border-border bg-muted/30 p-3">
            <Button
              type="button"
              variant="default"
              size="lg"
              className="w-full"
              disabled={!puedeEnviar}
              onClick={enviar}
            >
              {uploading && suppressUploadingUi ? (
                <>
                  <Check className="mr-2 h-4 w-4" />
                  {etiquetaEnvio}
                </>
              ) : uploading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" aria-hidden />
                  Enviando…
                </>
              ) : (
                <>
                  <Check className="mr-2 h-4 w-4" />
                  {etiquetaEnvio}
                </>
              )}
            </Button>
          </div>
        </div>
      )}

      {errorVisible && (
        <p className="text-sm text-destructive" role="alert">
          {errorVisible}
        </p>
      )}
    </div>
  );
}
