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

function instruccionesCamara(lado: LadoCapturaCamara, etiquetaDocumento?: string): string {
  const doc = etiquetaDocumento ?? 'documento';
  if (lado === 'pasaporte') {
    return 'Abra el pasaporte y encaje la página de datos en vertical, como una libreta';
  }
  if (lado === 'reverso') {
    return `Alinee el reverso del ${doc}: la banda MRZ (3 líneas de caracteres) debe quedar abajo en el marco`;
  }
  return `Encaje el anverso del ${doc} dentro del marco (cara con foto)`;
}

const CALENTAMIENTO_MS = 2000;
const INTERVALO_OCR_MS = 1300;
const FRAMES_OCR_LISTOS = 2;
const FRAMES_OCR_LISTOS_REVERSO = 1;
const PROGRESO_VERDE_REVERSO = 80;

export function CapturaCamaraDocumento({ abierto, lado, etiquetaDocumento, onCaptura, onCerrar }: CapturaCamaraDocumentoProps) {
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
  const [mensaje, setMensaje] = useState('Inicializando lector OCR…');
  const [analizando, setAnalizando] = useState(false);
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
      setMensaje('Inicializando lector OCR…');
      setOcrIniciando(true);
      setAnalizando(false);
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
        if (!cancelado) {
          setOcrIniciando(false);
          setMensaje('Coloque el documento dentro del marco');
        }
      })
      .catch(() => {
        if (!cancelado) {
          setOcrIniciando(false);
          setMensaje('Lector OCR no disponible — use captura manual');
        }
      });

    void (async () => {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
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
          setError('No se pudo acceder a la cámara. Compruebe los permisos o elija una imagen de la galería.');
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

    const ejecutarOcr = async () => {
      if (cancelado || capturandoRef.current) return;

      const video = videoRef.current;
      const marco = marcoRef.current;
      if (!video || !marco) return;

      const inicio = camaraListaEnRef.current;
      const enCalentamientoAhora = inicio !== null && Date.now() - inicio < CALENTAMIENTO_MS;
      setEnCalentamiento(enCalentamientoAhora);

      if (enCalentamientoAhora) {
        setMensaje('Coloque el documento dentro del marco');
        return;
      }

      setAnalizando(true);
      const resultado = await analizarMarcoConOcr(video, marco, lado);
      if (cancelado) return;

      setAnalizando(false);
      setProgreso(resultado.progreso);
      setMensaje(resultado.mensaje);

      if (resultado.datosLeidos) {
        ocrListosRef.current += 1;
        const framesNecesarios =
          lado === 'reverso' || lado === 'pasaporte' ? FRAMES_OCR_LISTOS_REVERSO : FRAMES_OCR_LISTOS;
        if (ocrListosRef.current >= framesNecesarios) {
          setAutoActivo(true);
          capturar();
        }
      } else {
        ocrListosRef.current = 0;
        setAutoActivo(false);
      }
    };

    void ejecutarOcr();
    const id = window.setInterval(() => void ejecutarOcr(), INTERVALO_OCR_MS);

    return () => {
      cancelado = true;
      window.clearInterval(id);
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

  return (
    <div className="fixed inset-0 z-50 flex flex-col bg-black">
      <div className="flex items-center justify-between px-4 py-3 text-white">
        <p className="text-sm font-medium">Foto del documento</p>
        <button type="button" onClick={onCerrar} className="rounded-full p-2 hover:bg-white/10" aria-label="Cerrar">
          <X className="h-5 w-5" />
        </button>
      </div>

      <div className="relative flex-1 overflow-hidden">
        <video ref={videoRef} playsInline muted className="h-full w-full object-cover" />

        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
          <div
            ref={marcoRef}
            className={cn(
              'relative rounded-lg border-2 shadow-[0_0_0_9999px_rgba(0,0,0,0.55)] transition-colors duration-500',
              marcoVerde ? 'border-emerald-400' : marcoAmbar ? 'border-amber-400' : 'border-white/90',
              esPasaporte ? 'h-[58%] w-auto max-w-[min(72vw,16rem)]' : 'h-[44%] w-[94%] max-w-md',
            )}
            style={{
              aspectRatio: esPasaporte
                ? `${PASAPORTE_ANCHO_MM} / ${PASAPORTE_ALTO_MM}`
                : `${ID1_ASPECT_RATIO} / 1`,
            }}
          >
            <span className="absolute -top-7 left-0 right-0 text-center text-[11px] font-medium uppercase tracking-wide text-white/90">
              {esPasaporte ? 'Página en vertical' : 'Documento horizontal'}
            </span>
          </div>

          <p className="mt-8 max-w-xs px-4 text-center text-xs text-white/80">
            {instruccionesCamara(lado, etiquetaDocumento)}
          </p>
          <p
            className={cn(
              'mt-3 flex max-w-sm items-center justify-center gap-2 px-6 text-center text-sm font-medium',
              marcoVerde ? 'text-emerald-300' : marcoAmbar ? 'text-amber-200' : 'text-white/90',
            )}
          >
            {analizando && <Loader2 className="h-4 w-4 shrink-0 animate-spin" />}
            {mensaje}
          </p>
          {!ocrIniciando && !enCalentamiento && (
            <p className="mt-2 text-[10px] text-white/45">
              OCR en tiempo real — la barra avanza al leer los datos del documento
            </p>
          )}
        </div>
      </div>

      <div className="space-y-2 bg-black/80 px-4 py-4">
        {error && <p className="text-center text-xs text-amber-300">{error}</p>}
        {!error && listo && !ocrIniciando && (
          <div className="space-y-1">
            <div className="flex justify-between text-[10px] text-white/60">
              <span>Lectura OCR</span>
              <span>{progreso}%</span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-white/20">
              <div
                className={cn(
                  'h-full rounded-full transition-all duration-500',
                  marcoVerde || progreso >= 100
                    ? 'bg-emerald-400'
                    : progreso >= 45
                      ? 'bg-amber-400'
                      : 'bg-white/55',
                )}
                style={{ width: `${progreso}%` }}
              />
            </div>
          </div>
        )}
        <Button
          type="button"
          className="w-full"
          size="lg"
          onClick={capturar}
          disabled={!listo || !!error || autoActivo || ocrIniciando}
        >
          <Camera className="mr-2 h-5 w-5" />
          {autoActivo
            ? 'Capturando…'
            : progreso >= 40 && esReversoMrz
              ? 'Capturar reverso ahora'
              : 'Capturar ahora'}
        </Button>
      </div>
    </div>
  );
}
