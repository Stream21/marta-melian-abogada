import { useEffect, useState } from 'react';
import { Camera, RotateCcw, RotateCw, Upload } from 'lucide-react';
import {
  esDocumentoVertical,
  renderDocumentoImage,
  rotacionInicialSugerida,
} from '@/lib/documento-imagen';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { EncuadreHorizontal } from './EncuadreHorizontal';

interface ImagenDocumentoCapturaProps {
  label: string;
  modo: 'cliente' | 'abogado';
  preview: string | null;
  inputId: string;
  inputRef: React.RefObject<HTMLInputElement | null>;
  isDragging?: boolean;
  rotation: number;
  onRotationChange: (deg: number) => void;
  onFileReady: (file: File, previewUrl: string) => void;
  onActivar: () => void;
  onDragEnter?: () => void;
  onDragLeave?: () => void;
  onDrop?: (file: File) => void;
}

export function ImagenDocumentoCaptura({
  label,
  modo,
  preview,
  inputId,
  inputRef,
  isDragging,
  rotation,
  onRotationChange,
  onFileReady,
  onActivar,
  onDragEnter,
  onDragLeave,
  onDrop,
}: ImagenDocumentoCapturaProps) {
  const [procesando, setProcesando] = useState(false);
  const [avisoVertical, setAvisoVertical] = useState(false);
  const [rawFile, setRawFile] = useState<File | null>(null);
  const esCliente = modo === 'cliente';

  useEffect(() => {
    if (!rawFile) return;

    let cancelado = false;
    setProcesando(true);

    void (async () => {
      try {
        const { previewUrl, normalizedFile } = await renderDocumentoImage(rawFile, rotation);
        const bitmap = await createImageBitmap(normalizedFile);
        const vertical = esDocumentoVertical(bitmap.width, bitmap.height);
        bitmap.close();
        if (!cancelado) {
          setAvisoVertical(vertical);
          onFileReady(normalizedFile, previewUrl);
        }
      } finally {
        if (!cancelado) setProcesando(false);
      }
    })();

    return () => {
      cancelado = true;
    };
  }, [rawFile, rotation, onFileReady]);

  const handleRawFile = async (file: File) => {
    setRawFile(file);
    const sugerida = await rotacionInicialSugerida(file);
    onRotationChange(sugerida);
  };

  const girar = (delta: number) => {
    onRotationChange((rotation + delta + 360) % 360);
  };

  const zonaProps = esCliente
    ? {}
    : {
        onDragEnter: (e: React.DragEvent) => {
          e.preventDefault();
          onDragEnter?.();
        },
        onDragOver: (e: React.DragEvent) => e.preventDefault(),
        onDragLeave: (e: React.DragEvent) => {
          e.preventDefault();
          onDragLeave?.();
        },
        onDrop: (e: React.DragEvent) => {
          e.preventDefault();
          const file = e.dataTransfer.files?.[0];
          if (file?.type.startsWith('image/')) {
            void handleRawFile(file);
            onDrop?.(file);
          }
        },
      };

  return (
    <div className="space-y-3">
      <div
        className={cn(
          'relative flex min-h-[240px] flex-col items-center justify-center gap-4 rounded-lg border border-dashed p-6 transition-colors',
          esCliente ? 'border-primary/30 bg-primary/5' : 'border-border bg-muted/30',
          isDragging && 'border-primary bg-primary/10',
        )}
        {...zonaProps}
      >
        {!preview && <EncuadreHorizontal />}

        {preview ? (
          <img
            src={preview}
            alt={label}
            className="relative z-10 max-h-48 max-w-full rounded object-contain shadow-sm"
          />
        ) : esCliente ? (
          <Camera className="relative z-10 h-12 w-12 text-primary" />
        ) : (
          <Upload className="relative z-10 h-12 w-12 text-muted-foreground" />
        )}

        <div className="relative z-10 text-center">
          <p className="font-medium">{label}</p>
          <p className="mt-1 max-w-sm text-xs text-muted-foreground">
            {esCliente
              ? 'Documento en horizontal, bien iluminado y sin reflejos.'
              : 'Suba una foto o pantallazo con el documento en horizontal.'}
          </p>
        </div>

        <div className="relative z-10 flex flex-wrap items-center justify-center gap-2">
          <Button type="button" onClick={onActivar} disabled={procesando}>
            {esCliente ? <Camera className="mr-2 h-4 w-4" /> : <Upload className="mr-2 h-4 w-4" />}
            {preview ? 'Cambiar imagen' : esCliente ? 'Activar cámara' : 'Subir imagen'}
          </Button>
          {preview && (
            <>
              <Button type="button" variant="outline" size="icon" onClick={() => girar(-90)} title="Girar a la izquierda">
                <RotateCcw className="h-4 w-4" />
              </Button>
              <Button type="button" variant="outline" size="icon" onClick={() => girar(90)} title="Girar a la derecha">
                <RotateCw className="h-4 w-4" />
              </Button>
            </>
          )}
        </div>

        <input
          id={inputId}
          ref={inputRef}
          type="file"
          accept="image/*"
          capture={esCliente ? 'environment' : undefined}
          className="sr-only"
          onChange={(e) => {
            const file = e.target.files?.[0];
            if (file) void handleRawFile(file);
            e.target.value = '';
          }}
        />
      </div>

      {avisoVertical && preview && (
        <p className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
          El documento sigue en vertical. Use los botones de giro hasta que se vea <strong>horizontal</strong>{' '}
          (más ancho que alto), como una tarjeta apoyada en la mesa.
        </p>
      )}

      {procesando && (
        <p className="text-center text-xs text-muted-foreground">Ajustando orientación…</p>
      )}
    </div>
  );
}
