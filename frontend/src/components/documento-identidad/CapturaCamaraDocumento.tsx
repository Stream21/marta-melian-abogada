import { useCallback, useEffect, useRef, useState } from 'react';
import { Camera, Loader2, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { capturarVideoRecortado } from '@/lib/captura-documento-calidad';
import {
  analizarMarcoConOcr,
  liberarOcrCamara,
  prepararOcrCamara,
} from '@/lib/captura-ocr-tiempo-real';
import { cn } from '@/lib/utils';
import { ID1_ASPECT_RATIO, PASAPORTE_ANCHO_MM, PASAPORTE_ALTO_MM } from '@/lib/documento-id1';

export type LadoCapturaCamara = 'anverso' | 'reverso' | 'pasaporte';

interface CapturaCamaraDocumentoProps {
  abierto: boolean;
  lado: LadoCapturaCamara;
  etiquetaDocumento?: string;
  onCaptura: (file: File) => void;
  onCerrar: () => void;
}

const CALENTAMIENTO_MS = 2000;
const PAUSA_ENTRE_OCR_MS = 400;
const PAUSA_OCR_OCUPADO_MS = 180;
const FRAMES_OCR_LISTOS = 2;
const FRAMES_OCR_LISTOS_REVERSO = 2;
const PROGRESO_VERDE_REVERSO = 80;

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });
}

/** Evita que la barra baje y “parpadee” con cada frame OCR. */
function suavizarProgreso(actual: number, siguiente: number): number {
  if (siguiente < 0) return actual;
  if (siguiente >= actual) return siguiente;
  // Solo retrocede si el OCR pierde claramente el documento.
  if (siguiente <= actual - 25) return siguiente;
  return actual;
}

export function CapturaCamaraDocumento({
  abierto,
  lado,
  onCaptura,
  onCerrar,
}: CapturaCamaraDocumentoProps) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const marcoRef = useRef<HTMLDivElement>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const ocrListosRef = useRef(0);
  const capturandoRef = useRef(false);
  const camaraListaEnRef = useRef<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [listo, setListo] = useState(false);
  const [ocrIniciando, setOcrIniciando] = useState(true);
  const [progreso, setProgreso] = useState(0);
  const [autoActivo, setAutoActivo] = useState(false);
  const [enCalentamiento, setEnCalentamiento] = useState(true);

  const detenerCamara = useCallback(() => {
    streamRef.current?.getTracks().forEach((t) => t.stop());
    streamRef.current = null;
    if (videoRef.current) {
      videoRef.current.srcObject = null;
    }
  }, []);

  const capturar = useCallback(() => {
    const video = videoRef.current;
    const marco = marcoRef.current;
    if (!video || !marco || capturandoRef.current) return;

    capturandoRef.current = true;

    void (async () => {
      try {
        const file = await capturarVideoRecortado(
          video,
          marco,
          `captura-${lado}-${Date.now()}.jpg`,
        );
        if (!file) return;
        detenerCamara();
        onCaptura(file);
      } finally {
        capturandoRef.current = false;
      }
    })();
  }, [detenerCamara, lado, onCaptura]);

  useEffect(() => {
    if (!abierto) {
      detenerCamara();
      void liberarOcrCamara();
      setListo(false);
      setError(null);
      setProgreso(0);
      setOcrIniciando(true);
      setAutoActivo(false);
      setEnCalentamiento(true);
      ocrListosRef.current = 0;
      capturandoRef.current = false;
      camaraListaEnRef.current = null;
      return;
    }

    let cancelado = false;

    void prepararOcrCamara()
      .then(() => {
        if (!cancelado) setOcrIniciando(false);
      })
      .catch(() => {
        if (!cancelado) setOcrIniciando(false);
      });

    void (async () => {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: {
            facingMode: { ideal: 'environment' },
            width: { ideal: 1920 },
            height: { ideal: 1080 },
          },
          audio: false,
        });
        if (cancelado) {
          stream.getTracks().forEach((t) => t.stop());
          return;
        }
        streamRef.current = stream;
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          await videoRef.current.play();
          camaraListaEnRef.current = Date.now();
          setListo(true);
        }
      } catch {
        if (!cancelado) {
          setError(
            'No se pudo acceder a la cámara. Compruebe los permisos.',
          );
        }
      }
    })();

    return () => {
      cancelado = true;
      detenerCamara();
      void liberarOcrCamara();
    };
  }, [abierto, detenerCamara]);

  useEffect(() => {
    if (!abierto || !listo || error || ocrIniciando) return;

    let cancelado = false;

    const bucleOcr = async () => {
      while (!cancelado && !capturandoRef.current) {
        const video = videoRef.current;
        const marco = marcoRef.current;
        if (!video || !marco) {
          await sleep(PAUSA_ENTRE_OCR_MS);
          continue;
        }

        const inicio = camaraListaEnRef.current;
        const enCalentamientoAhora =
          inicio !== null && Date.now() - inicio < CALENTAMIENTO_MS;
        setEnCalentamiento(enCalentamientoAhora);

        if (enCalentamientoAhora) {
          await sleep(PAUSA_ENTRE_OCR_MS);
          continue;
        }

        const resultado = await analizarMarcoConOcr(video, marco, lado);
        if (cancelado || capturandoRef.current) break;

        // Frame ocupado: no tocar progreso ni el contador de éxitos.
        if (resultado.analizando) {
          await sleep(PAUSA_OCR_OCUPADO_MS);
          continue;
        }

        setProgreso((prev) => suavizarProgreso(prev, resultado.progreso));

        if (resultado.datosLeidos) {
          ocrListosRef.current += 1;
          const framesNecesarios =
            lado === 'reverso' || lado === 'pasaporte'
              ? FRAMES_OCR_LISTOS_REVERSO
              : FRAMES_OCR_LISTOS;
          if (ocrListosRef.current >= framesNecesarios) {
            setAutoActivo(true);
            capturar();
            break;
          }
        } else {
          ocrListosRef.current = 0;
          setAutoActivo(false);
        }

        await sleep(PAUSA_ENTRE_OCR_MS);
      }
    };

    void bucleOcr();

    return () => {
      cancelado = true;
    };
  }, [abierto, listo, error, ocrIniciando, lado, capturar]);

  if (!abierto) return null;

  const esPasaporte = lado === 'pasaporte';
  const esReversoMrz = lado === 'reverso' || esPasaporte;
  const marcoVerde =
    progreso >= 100 ||
    autoActivo ||
    (esReversoMrz && progreso >= PROGRESO_VERDE_REVERSO);
  const marcoAmbar = !marcoVerde && progreso >= 45;
  const mostrandoBarra = !error && listo && !ocrIniciando && !enCalentamiento;

  return (
    <div className="fixed inset-0 z-50 bg-black">
      <video
        ref={videoRef}
        playsInline
        muted
        className="absolute inset-0 h-full w-full object-cover"
      />

      {/* Oscurecido fuera del marco */}
      <div className="pointer-events-none absolute inset-0 flex items-center justify-center px-3 pb-[max(5.5rem,env(safe-area-inset-bottom))] pt-[max(3.5rem,env(safe-area-inset-top))]">
        <div
          ref={marcoRef}
          className={cn(
            'relative w-full max-w-lg transition-[border-color,box-shadow] duration-300 ease-out',
            'shadow-[0_0_0_9999px_rgba(0,0,0,0.62)]',
            esPasaporte
              ? 'max-h-[min(72vh,34rem)] w-auto max-w-[min(78vw,18rem)]'
              : 'max-h-[min(42vh,16rem)]',
          )}
          style={{
            aspectRatio: esPasaporte
              ? `${PASAPORTE_ANCHO_MM} / ${PASAPORTE_ALTO_MM}`
              : `${ID1_ASPECT_RATIO} / 1`,
          }}
        >
          {/* Esquinas tipo visor */}
          <span
            aria-hidden
            className={cn(
              'absolute left-0 top-0 h-7 w-7 border-l-[3px] border-t-[3px] transition-colors duration-300',
              marcoVerde
                ? 'border-emerald-400'
                : marcoAmbar
                  ? 'border-amber-400'
                  : 'border-white',
            )}
          />
          <span
            aria-hidden
            className={cn(
              'absolute right-0 top-0 h-7 w-7 border-r-[3px] border-t-[3px] transition-colors duration-300',
              marcoVerde
                ? 'border-emerald-400'
                : marcoAmbar
                  ? 'border-amber-400'
                  : 'border-white',
            )}
          />
          <span
            aria-hidden
            className={cn(
              'absolute bottom-0 left-0 h-7 w-7 border-b-[3px] border-l-[3px] transition-colors duration-300',
              marcoVerde
                ? 'border-emerald-400'
                : marcoAmbar
                  ? 'border-amber-400'
                  : 'border-white',
            )}
          />
          <span
            aria-hidden
            className={cn(
              'absolute bottom-0 right-0 h-7 w-7 border-b-[3px] border-r-[3px] transition-colors duration-300',
              marcoVerde
                ? 'border-emerald-400'
                : marcoAmbar
                  ? 'border-amber-400'
                  : 'border-white',
            )}
          />

          {/* Barra de lectura — único feedback de evaluación */}
          {mostrandoBarra && (
            <div className="absolute inset-x-3 bottom-3">
              <div
                className="h-1.5 overflow-hidden rounded-full bg-white/25"
                role="progressbar"
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={progreso}
                aria-label="Lectura del documento"
              >
                <div
                  className={cn(
                    'h-full rounded-full transition-[width,background-color] duration-300 ease-out motion-reduce:transition-none',
                    marcoVerde
                      ? 'bg-emerald-400'
                      : marcoAmbar
                        ? 'bg-amber-400'
                        : 'bg-white',
                  )}
                  style={{ width: `${Math.max(progreso, 4)}%` }}
                />
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Cerrar */}
      <button
        type="button"
        onClick={onCerrar}
        className="absolute right-3 top-[max(0.75rem,env(safe-area-inset-top))] z-10 flex h-11 w-11 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-sm transition-colors hover:bg-black/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
        aria-label="Cerrar"
      >
        <X className="h-5 w-5" />
      </button>

      {/* Preparando OCR — un solo estado estable */}
      {(ocrIniciando || !listo) && !error && (
        <div className="absolute inset-0 z-10 flex items-center justify-center bg-black/40">
          <Loader2 className="h-8 w-8 animate-spin text-white motion-reduce:animate-none" />
        </div>
      )}

      {/* Pie mínimo */}
      <div className="absolute inset-x-0 bottom-0 z-10 space-y-3 bg-gradient-to-t from-black/80 via-black/50 to-transparent px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-10">
        {error && (
          <p className="text-center text-sm text-amber-200">{error}</p>
        )}
        <Button
          type="button"
          size="lg"
          variant="secondary"
          className="h-12 w-full bg-white/15 text-white hover:bg-white/25"
          onClick={capturar}
          disabled={!listo || !!error || autoActivo || ocrIniciando}
        >
          <Camera className="mr-2 h-5 w-5" />
          {autoActivo ? 'Capturando…' : 'Capturar manualmente'}
        </Button>
      </div>
    </div>
  );
}
