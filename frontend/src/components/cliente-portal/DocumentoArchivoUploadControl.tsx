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
  const contadorLabel = multiple ? `${seleccionados.length}/${limite} archivos` : null;

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
          <p className="text-xs font-medium text-foreground">{documentoUploadLimiteLabel(tipo, maxImagenes)}</p>
          <p className="mt-0.5 text-xs text-muted-foreground">{documentoUploadLimiteDetalle(tipo, maxImagenes)}</p>
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

      <div className="flex flex-wrap items-center gap-2">
        {multiple && seleccionados.length < limite && (
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={uploading}
            onClick={() => inputRef.current?.click()}
          >
            <Plus className="mr-2 h-4 w-4" />
            Añadir {seleccionados.length === 0 ? 'archivo' : 'otro archivo'}
          </Button>
        )}

        {!multiple && (
          <Button
            type="button"
            variant={variant}
            size="sm"
            disabled={uploading}
            onClick={() => inputRef.current?.click()}
          >
            <Upload className="mr-2 h-4 w-4" />
            Elegir archivo
          </Button>
        )}

        {contadorLabel && (
          <span className="text-xs text-muted-foreground">{contadorLabel}</span>
        )}
      </div>

      {seleccionados.length > 0 && (
        <div className="space-y-3 rounded-lg border border-border bg-background p-3">
          <p className="text-xs text-muted-foreground">
            Revise los archivos seleccionados. Pulse «Listo» solo cuando estén correctos; después no podrá
            modificarlos hasta que su abogado los revise.
          </p>
          <ul className="space-y-2">
            {seleccionados.map((file, index) => (
              <li key={`${file.name}-${index}`} className="flex items-center justify-between gap-2 text-xs">
                <span className="flex min-w-0 items-center gap-2">
                  <FileText className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                  <span className="truncate">{file.name}</span>
                </span>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  className="h-7 w-7 shrink-0"
                  disabled={uploading}
                  onClick={() => quitarArchivo(index)}
                >
                  <X className="h-3.5 w-3.5" />
                </Button>
              </li>
            ))}
          </ul>

          <div className="flex flex-wrap items-center gap-2">
            <Button
              type="button"
              variant="default"
              size="sm"
              className="w-full sm:w-auto"
              disabled={!puedeEnviar}
              onClick={enviar}
            >
              {uploading && suppressUploadingUi ? (
                <>
                  <Check className="mr-2 h-4 w-4" />
                  {seleccionados.length > 1
                    ? `Listo — enviar ${seleccionados.length} archivos`
                    : readyLabel || submitLabel}
                </>
              ) : uploading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" aria-hidden />
                  Enviando…
                </>
              ) : (
                <>
                  <Check className="mr-2 h-4 w-4" />
                  {seleccionados.length > 1
                    ? `Listo — enviar ${seleccionados.length} archivos`
                    : readyLabel || submitLabel}
                </>
              )}
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              disabled={uploading}
              onClick={() => {
                setSeleccionados([]);
                setErrorLocal(null);
              }}
            >
              Volver a elegir archivos
            </Button>
          </div>
        </div>
      )}

      {errorVisible && <p className="text-xs text-destructive">{errorVisible}</p>}
    </div>
  );
}
