import { useEffect, useRef, useState } from 'react';
import { Camera, ImageIcon, RotateCcw, RotateCw, Upload } from 'lucide-react';
import {
  esDocumentoVertical,
  renderDocumentoImage,
  rotacionInicialSugerida,
} from '@/lib/documento-imagen';
import { esDispositivoMovil } from '@/lib/device';
import { ID1_ASPECT_RATIO, PASAPORTE_ASPECT_RATIO } from '@/lib/documento-id1';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { CapturaCamaraDocumento, type LadoCapturaCamara } from './CapturaCamaraDocumento';
import { EncuadreHorizontal } from './EncuadreHorizontal';
import { InstruccionesCapturaDocumentoDialog } from './InstruccionesCapturaDocumentoDialog';

function clickInputRef(ref: React.RefObject<HTMLInputElement | null>): void {
  ref.current?.click();
}

interface ImagenDocumentoCapturaProps {
  label: string;
  modo: 'cliente' | 'abogado';
  /** Solo en modo cliente: activa cámara avanzada con marco guía en móvil. */
  varianteCaptura?: 'identidad' | 'general';
  /** UI mínima: un solo CTA «Escanear documento» dentro del marco. */
  uiSimplificada?: boolean;
  ladoCamara?: LadoCapturaCamara;
  /** Etiqueta del tipo de documento (p. ej. NIE, DNI / NIE) para el diálogo de instrucciones. */
  etiquetaDocumento?: string;
  preview: string | null;
  inputId: string;
  inputRef: React.RefObject<HTMLInputElement | null>;
  galeriaInputRef?: React.RefObject<HTMLInputElement | null>;
  isDragging?: boolean;
  rotation: number;
  onRotationChange: (deg: number) => void;
  onFileReady: (file: File, previewUrl: string) => void;
  onActivar?: () => void;
  onDragEnter?: () => void;
  onDragLeave?: () => void;
  onDrop?: (file: File) => void;
}

export function ImagenDocumentoCaptura({
  label,
  modo,
  varianteCaptura = 'general',
  uiSimplificada = false,
  ladoCamara = 'anverso',
  etiquetaDocumento,
  preview,
  inputId,
  inputRef,
  galeriaInputRef,
  isDragging,
  rotation,
  onRotationChange,
  onFileReady,
  onActivar,
  onDragEnter,
  onDragLeave,
  onDrop,
}: ImagenDocumentoCapturaProps) {
  const galeriaRefLocal = useRef<HTMLInputElement | null>(null);
  const galeriaRef: React.RefObject<HTMLInputElement | null> = galeriaInputRef ?? galeriaRefLocal;
  const [procesando, setProcesando] = useState(false);
  const [avisoVertical, setAvisoVertical] = useState(false);
  const [rawFile, setRawFile] = useState<File | null>(null);
  const [camaraAbierta, setCamaraAbierta] = useState(false);
  const [instruccionesAbiertas, setInstruccionesAbiertas] = useState(false);
  const [esMovil, setEsMovil] = useState(false);

  const esCliente = modo === 'cliente';
  const capturaAvanzada = esCliente && varianteCaptura === 'identidad';
  const soloCamaraOcr = capturaAvanzada;

  useEffect(() => {
    setEsMovil(esDispositivoMovil());
  }, []);

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

  const abrirGaleria = () => {
    clickInputRef(galeriaRef);
  };

  const abrirFichero = () => {
    if (onActivar) {
      onActivar();
    } else {
      clickInputRef(inputRef);
    }
  };

  const abrirCamara = () => {
    if (soloCamaraOcr) {
      setInstruccionesAbiertas(true);
      return;
    }
    clickInputRef(inputRef);
  };

  const etiquetaLadoCorto =
    ladoCamara === 'reverso' ? 'reverso' : ladoCamara === 'pasaporte' ? 'pasaporte' : 'anverso';

  const aspectRatioCaptura =
    ladoCamara === 'pasaporte' ? PASAPORTE_ASPECT_RATIO : ID1_ASPECT_RATIO;

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

  const textoAyuda = (() => {
    if (!esCliente) return 'Suba una foto o pantallazo con el documento en horizontal.';
    if (soloCamaraOcr) return 'Abra la cámara, encaje el documento en el marco y espere la lectura OCR.';
    if (esMovil) return 'Use la cámara con marco guía o elija una imagen de la galería.';
    return 'Seleccione una imagen clara del documento en horizontal desde su equipo.';
  })();

  if (uiSimplificada && soloCamaraOcr) {
    return (
      <div className="space-y-4">
        {!preview ? (
          <button
            type="button"
            onClick={abrirCamara}
            disabled={procesando}
            className={cn(
              'group relative w-full overflow-hidden rounded-2xl border border-border bg-card text-left shadow-sm',
              'transition-colors hover:border-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
              'disabled:pointer-events-none disabled:opacity-60',
            )}
          >
            <div
              className="doc-scan-frame mx-4 mb-4 mt-4"
              style={{ aspectRatio: String(aspectRatioCaptura) }}
            >
              <span
                aria-hidden
                className="pointer-events-none absolute inset-3 border border-primary/20"
              />
              <span aria-hidden className="pointer-events-none absolute left-2 top-2 h-5 w-5 border-l-2 border-t-2 border-primary" />
              <span aria-hidden className="pointer-events-none absolute right-2 top-2 h-5 w-5 border-r-2 border-t-2 border-primary" />
              <span aria-hidden className="pointer-events-none absolute bottom-2 left-2 h-5 w-5 border-b-2 border-l-2 border-primary" />
              <span aria-hidden className="pointer-events-none absolute bottom-2 right-2 h-5 w-5 border-b-2 border-r-2 border-primary" />
              <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 px-4 text-center">
                <span className="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm transition-transform group-hover:scale-105">
                  <Camera className="h-5 w-5" />
                </span>
                <span className="text-sm font-semibold text-foreground">
                  Escanear {etiquetaLadoCorto}
                </span>
                <span className="max-w-[14rem] text-xs text-muted-foreground">
                  Buena luz, sin reflejos. El documento debe verse entero.
                </span>
              </div>
            </div>
          </button>
        ) : (
          <div className="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
            <div className="bg-muted/20 px-3 py-4">
              <img
                src={preview}
                alt={label}
                className="mx-auto max-h-52 w-full rounded-lg object-contain"
              />
            </div>
            <div className="flex flex-wrap items-center justify-center gap-2 border-t border-border p-3">
              <Button type="button" variant="outline" onClick={abrirCamara} disabled={procesando}>
                <Camera className="mr-2 h-4 w-4" />
                Repetir
              </Button>
              <Button type="button" variant="outline" size="icon" onClick={() => girar(-90)} title="Girar a la izquierda">
                <RotateCcw className="h-4 w-4" />
              </Button>
              <Button type="button" variant="outline" size="icon" onClick={() => girar(90)} title="Girar a la derecha">
                <RotateCw className="h-4 w-4" />
              </Button>
            </div>
          </div>
        )}

        <InstruccionesCapturaDocumentoDialog
          abierto={instruccionesAbiertas}
          lado={ladoCamara}
          etiquetaDocumento={etiquetaDocumento}
          onContinuar={() => {
            setInstruccionesAbiertas(false);
            setCamaraAbierta(true);
          }}
          onCancelar={() => setInstruccionesAbiertas(false)}
        />
        <CapturaCamaraDocumento
          abierto={camaraAbierta}
          lado={ladoCamara}
          etiquetaDocumento={etiquetaDocumento}
          onCerrar={() => setCamaraAbierta(false)}
          onCaptura={(file) => {
            setCamaraAbierta(false);
            void handleRawFile(file);
          }}
        />

        {avisoVertical && preview && (
          <p className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
            El documento sigue en vertical. Use los botones de giro hasta que se vea <strong>horizontal</strong>.
          </p>
        )}
        {procesando && (
          <p className="text-center text-xs text-muted-foreground">Ajustando orientación…</p>
        )}
      </div>
    );
  }

  // UI moderna para abogado / galería: misma composición, sin cámara.
  if (uiSimplificada) {
    return (
      <div className="space-y-4">
        {!preview ? (
          <div
            role="button"
            tabIndex={0}
            onClick={abrirFichero}
            onKeyDown={(e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                abrirFichero();
              }
            }}
            className={cn(
              'group flex w-full min-h-[11rem] cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-border bg-card px-4 py-8 text-center shadow-sm',
              'transition-colors hover:border-primary/40 hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
              procesando && 'pointer-events-none opacity-60',
              isDragging && 'border-primary bg-primary/5',
            )}
            {...zonaProps}
          >
            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm transition-transform group-hover:scale-105">
              <ImageIcon className="h-5 w-5" />
            </span>
            <span className="text-sm font-semibold text-foreground">
              Seleccionar {etiquetaLadoCorto}
            </span>
            <span className="max-w-[16rem] text-xs text-muted-foreground">
              JPG o PNG desde su equipo. Arrastre aquí o haga clic.
            </span>
          </div>
        ) : (
          <div className="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
            <div className="bg-muted/20 px-3 py-4">
              <img
                src={preview}
                alt={label}
                className="mx-auto max-h-52 w-full rounded-lg object-contain"
              />
            </div>
            <div className="flex flex-wrap items-center justify-center gap-2 border-t border-border p-3">
              <Button type="button" variant="outline" onClick={abrirFichero} disabled={procesando}>
                <Upload className="mr-2 h-4 w-4" />
                Cambiar imagen
              </Button>
              <Button type="button" variant="outline" size="icon" onClick={() => girar(-90)} title="Girar a la izquierda">
                <RotateCcw className="h-4 w-4" />
              </Button>
              <Button type="button" variant="outline" size="icon" onClick={() => girar(90)} title="Girar a la derecha">
                <RotateCw className="h-4 w-4" />
              </Button>
            </div>
          </div>
        )}

        <input
          id={inputId}
          ref={inputRef as React.Ref<HTMLInputElement>}
          type="file"
          accept="image/*"
          className="sr-only"
          onChange={(e) => {
            const file = e.target.files?.[0];
            if (file) void handleRawFile(file);
            e.target.value = '';
          }}
        />

        {avisoVertical && preview && (
          <p className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
            El documento sigue en vertical. Use los botones de giro hasta que se vea <strong>horizontal</strong>.
          </p>
        )}
        {procesando && (
          <p className="text-center text-xs text-muted-foreground">Ajustando orientación…</p>
        )}
      </div>
    );
  }

  return (
    <div className="space-y-3">
      <section
        className={cn(
          'overflow-hidden rounded-xl border border-dashed transition-colors',
          esCliente ? 'border-primary/30 bg-primary/5' : 'border-border bg-muted/30',
          isDragging && 'border-primary bg-primary/10',
        )}
        {...zonaProps}
      >
        <div className="flex flex-col items-center gap-4 px-4 py-5">
          {preview ? (
            <img
              src={preview}
              alt={label}
              className="max-h-48 max-w-full rounded-lg object-contain shadow-sm"
            />
          ) : (
            <>
              {!esMovil && <EncuadreHorizontal />}
              <div className="flex flex-col items-center gap-2 text-center">
                <div className="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10">
                  {esCliente && esMovil ? (
                    <Camera className="h-7 w-7 text-primary" />
                  ) : (
                    <Upload className="h-7 w-7 text-muted-foreground" />
                  )}
                </div>
                <p className="text-sm font-semibold text-foreground">{label}</p>
                {!esMovil && (
                  <p className="max-w-xs text-xs text-muted-foreground">{textoAyuda}</p>
                )}
              </div>
              {esMovil && <EncuadreHorizontal compacto className="max-w-xs" />}
            </>
          )}
        </div>

        <div className="flex flex-wrap items-center justify-center gap-2 border-t border-primary/10 bg-card/60 px-4 py-3">
          {soloCamaraOcr || (esCliente && esMovil) ? (
            <Button
              type="button"
              onClick={abrirCamara}
              disabled={procesando}
              className={soloCamaraOcr ? 'min-w-[10rem]' : 'min-w-[8.5rem]'}
              size={soloCamaraOcr ? 'lg' : 'default'}
            >
              <Camera className="mr-2 h-4 w-4" />
              {preview ? 'Nueva foto' : soloCamaraOcr ? 'Abrir cámara' : 'Hacer foto'}
            </Button>
          ) : (
            <Button type="button" onClick={abrirFichero} disabled={procesando}>
              <Upload className="mr-2 h-4 w-4" />
              {preview ? 'Cambiar imagen' : esCliente ? 'Seleccionar archivo' : 'Subir imagen'}
            </Button>
          )}
          {esCliente && esMovil && !soloCamaraOcr && (
            <Button type="button" variant="outline" onClick={abrirGaleria} disabled={procesando}>
              <ImageIcon className="mr-2 h-4 w-4" />
              Galería
            </Button>
          )}
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

        {!soloCamaraOcr && (
          <>
            <input
              id={inputId}
              ref={inputRef as React.Ref<HTMLInputElement>}
              type="file"
              accept="image/*"
              capture={esCliente && esMovil ? 'environment' : undefined}
              className="sr-only"
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (file) void handleRawFile(file);
                e.target.value = '';
              }}
            />
            <input
              ref={galeriaRef as React.Ref<HTMLInputElement>}
              type="file"
              accept="image/*"
              className="sr-only"
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (file) void handleRawFile(file);
                e.target.value = '';
              }}
            />
          </>
        )}
      </section>

      {(soloCamaraOcr || esMovil) && !preview && (
        <p className="px-1 text-center text-xs text-muted-foreground">{textoAyuda}</p>
      )}

      {capturaAvanzada && (
        <>
          <InstruccionesCapturaDocumentoDialog
            abierto={instruccionesAbiertas}
            lado={ladoCamara}
            etiquetaDocumento={etiquetaDocumento}
            onContinuar={() => {
              setInstruccionesAbiertas(false);
              setCamaraAbierta(true);
            }}
            onCancelar={() => setInstruccionesAbiertas(false)}
          />
          <CapturaCamaraDocumento
            abierto={camaraAbierta}
            lado={ladoCamara}
            etiquetaDocumento={etiquetaDocumento}
            onCerrar={() => setCamaraAbierta(false)}
            onCaptura={(file) => {
              setCamaraAbierta(false);
              void handleRawFile(file);
            }}
          />
        </>
      )}

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
