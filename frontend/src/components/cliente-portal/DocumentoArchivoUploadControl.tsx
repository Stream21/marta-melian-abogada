import { useEffect, useRef, useState } from 'react';
import { Check, FileText, Loader2, Plus, Send, Upload, X } from 'lucide-react';
import type { TipoDocumentoRequerido } from '@/api/client';
import { Button } from '@/components/ui/button';
import { FileUploadProgressPanel } from '@/components/cliente-portal/FileUploadProgressPanel';
import {
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

function PasoIndicador({
  paso,
  activo,
  hecho,
  label,
}: {
  paso: number;
  activo: boolean;
  hecho: boolean;
  label: string;
}) {
  return (
    <div
      className={cn(
        'flex min-w-0 flex-1 items-center gap-2 rounded-lg px-2.5 py-2',
        activo && 'bg-primary/10',
        hecho && !activo && 'bg-muted/60',
      )}
    >
      <span
        className={cn(
          'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold',
          activo && 'bg-primary text-primary-foreground',
          hecho && !activo && 'bg-emerald-600 text-white',
          !activo && !hecho && 'bg-muted text-muted-foreground',
        )}
        aria-hidden
      >
        {hecho && !activo ? <Check className="h-3.5 w-3.5" strokeWidth={3} /> : paso}
      </span>
      <span
        className={cn(
          'truncate text-xs font-semibold',
          activo ? 'text-primary' : hecho ? 'text-foreground' : 'text-muted-foreground',
        )}
      >
        {label}
      </span>
    </div>
  );
}

export function DocumentoArchivoUploadControl({
  tipo,
  maxImagenes,
  uploading = false,
  error,
  submitLabel = 'Subir documento',
  readyLabel = 'Enviar documento',
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
      setErrorLocal('Primero elija un archivo y después pulse Enviar.');
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
      ? `Enviar ${seleccionados.length} archivos`
      : readyLabel || submitLabel;

  const abrirSelector = () => {
    if (!uploading) inputRef.current?.click();
  };

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

      <div className="flex items-stretch gap-1.5" aria-label="Pasos para enviar el documento">
        <PasoIndicador
          paso={1}
          activo={!haySeleccion}
          hecho={haySeleccion}
          label="Elegir archivo"
        />
        <span className="flex items-center text-muted-foreground/50" aria-hidden>
          →
        </span>
        <PasoIndicador
          paso={2}
          activo={haySeleccion}
          hecho={false}
          label="Enviar"
        />
      </div>

      {showLimiteHeader && (
        <p className="text-xs text-muted-foreground">
          {documentoUploadLimiteLabel(tipo, maxImagenes)}
          {!haySeleccion && ' Primero elija el archivo; después lo enviará.'}
        </p>
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
        <button
          type="button"
          disabled={uploading}
          onClick={abrirSelector}
          className={cn(
            'group flex w-full flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed px-4 py-6 text-center',
            'border-primary/40 bg-primary/5 transition-colors',
            'hover:border-primary hover:bg-primary/10',
            'active:scale-[0.99] active:bg-primary/15',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
            'disabled:pointer-events-none disabled:opacity-60',
            variant === 'default' && 'border-primary/50',
          )}
        >
          <span className="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm">
            {multiple ? (
              <Plus className="h-5 w-5" aria-hidden />
            ) : (
              <Upload className="h-5 w-5" aria-hidden />
            )}
          </span>
          <span className="space-y-1">
            <span className="block text-sm font-bold text-primary">
              {multiple
                ? seleccionados.length === 0
                  ? 'Pulse para elegir archivos'
                  : 'Pulse para añadir otro archivo'
                : 'Pulse para elegir el archivo'}
            </span>
            <span className="block text-xs text-muted-foreground">
              Foto o PDF desde el teléfono o la galería
            </span>
          </span>
          <span
            className={cn(
              'inline-flex min-h-[44px] items-center justify-center rounded-md px-4 text-sm font-semibold',
              'bg-primary text-primary-foreground shadow-sm',
              'group-active:opacity-90',
            )}
          >
            {multiple ? 'Añadir archivo' : 'Elegir archivo'}
          </span>
        </button>
      )}

      {haySeleccion && (
        <div className="overflow-hidden rounded-xl border border-primary/25 bg-card shadow-sm">
          <div className="border-b border-border/70 bg-primary/5 px-3 py-2.5">
            <p className="text-sm font-semibold text-foreground">
              {seleccionados.length === 1
                ? 'Archivo elegido — ahora envíelo'
                : `${seleccionados.length} archivos elegidos — ahora envíelos`}
            </p>
            <p className="mt-0.5 text-xs text-muted-foreground">
              Revise el nombre. Al enviar no podrá cambiarlo hasta que su abogado lo revise.
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
                onClick={abrirSelector}
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
              className="min-h-[48px] w-full text-base"
              disabled={!puedeEnviar}
              onClick={enviar}
            >
              {uploading && suppressUploadingUi ? (
                <>
                  <Send className="mr-2 h-4 w-4" />
                  {etiquetaEnvio}
                </>
              ) : uploading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" aria-hidden />
                  Enviando…
                </>
              ) : (
                <>
                  <Send className="mr-2 h-4 w-4" />
                  {etiquetaEnvio}
                </>
              )}
            </Button>
            <p className="mt-2 text-center text-[11px] text-muted-foreground">
              Paso 2 de 2: pulse para enviar a su abogado
            </p>
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
