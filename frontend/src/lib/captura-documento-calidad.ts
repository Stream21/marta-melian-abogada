import { ID1_ASPECT_RATIO } from '@/lib/documento-id1';

export interface MarcoRelativo {
  top: number;
  left: number;
  width: number;
  height: number;
}

export interface RegionVideoPixels {
  sx: number;
  sy: number;
  sw: number;
  sh: number;
}

export type LadoCapturaEval = 'anverso' | 'reverso' | 'pasaporte';

export interface EvaluacionCaptura {
  puntaje: number;
  nitidez: number;
  brillo: number;
  documentoDetectado: boolean;
  documentoCompleto: boolean;
  textoLegible: boolean;
  lista: boolean;
  mensaje: string;
}

const MUESTRA_ANCHO = 280;
const MUESTRA_ALTO = Math.round(MUESTRA_ANCHO / ID1_ASPECT_RATIO);

const NITIDEZ_MINIMA = 165;
const NITIDEZ_MRZ_MINIMA = 145;
const BRILLO_MIN = 50;
const BRILLO_MAX = 210;
const CONTRASTE_MIN = 22;
const COBERTURA_MIN = 0.5;
const MARGEN_INTERNO_MIN = 0.035;
const TRANSICIONES_MRZ_FILA = 12;

/** Marco guía DNI/NIE (horizontal) o pasaporte (vertical, libreta). */
export function marcoGuiaRelativo(esPasaporte: boolean): MarcoRelativo {
  if (esPasaporte) {
    return { top: 0.1, left: 0.16, width: 0.68, height: 0.76 };
  }
  return { top: 0.26, left: 0.04, width: 0.92, height: 0.44 };
}

export function regionMarcoEnVideoPixels(
  video: HTMLVideoElement,
  marcoElement: HTMLElement,
): RegionVideoPixels | null {
  const vw = video.videoWidth;
  const vh = video.videoHeight;
  if (!vw || !vh) return null;

  const videoRect = video.getBoundingClientRect();
  const marcoRect = marcoElement.getBoundingClientRect();
  if (videoRect.width <= 0 || videoRect.height <= 0) return null;

  const scale = Math.max(videoRect.width / vw, videoRect.height / vh);
  const offsetX = (vw * scale - videoRect.width) / 2;
  const offsetY = (vh * scale - videoRect.height) / 2;

  const fx = marcoRect.left - videoRect.left;
  const fy = marcoRect.top - videoRect.top;

  let sx = Math.round((offsetX + fx) / scale);
  let sy = Math.round((offsetY + fy) / scale);
  let sw = Math.round(marcoRect.width / scale);
  let sh = Math.round(marcoRect.height / scale);

  sx = Math.max(0, Math.min(sx, vw - 1));
  sy = Math.max(0, Math.min(sy, vh - 1));
  sw = Math.max(1, Math.min(sw, vw - sx));
  sh = Math.max(1, Math.min(sh, vh - sy));

  return { sx, sy, sw, sh };
}

export function regionDesdeMarcoRelativo(
  video: HTMLVideoElement,
  marco: MarcoRelativo,
): RegionVideoPixels | null {
  const vw = video.videoWidth;
  const vh = video.videoHeight;
  if (!vw || !vh) return null;

  return {
    sx: Math.round(marco.left * vw),
    sy: Math.round(marco.top * vh),
    sw: Math.max(1, Math.round(marco.width * vw)),
    sh: Math.max(1, Math.round(marco.height * vh)),
  };
}

export async function capturarVideoRecortado(
  video: HTMLVideoElement,
  marcoElement: HTMLElement,
  nombreArchivo: string,
): Promise<File | null> {
  const region = regionMarcoEnVideoPixels(video, marcoElement);
  if (!region) return null;

  const canvas = document.createElement('canvas');
  canvas.width = region.sw;
  canvas.height = region.sh;
  const ctx = canvas.getContext('2d');
  if (!ctx) return null;

  ctx.drawImage(
    video,
    region.sx,
    region.sy,
    region.sw,
    region.sh,
    0,
    0,
    region.sw,
    region.sh,
  );

  const blob = await new Promise<Blob | null>((resolve) => {
    canvas.toBlob((b) => resolve(b), 'image/jpeg', 0.92);
  });
  if (!blob) return null;

  return new File([blob], nombreArchivo, { type: 'image/jpeg' });
}

export function evaluarFrameCaptura(
  video: HTMLVideoElement,
  marco: MarcoRelativo | HTMLElement,
  lado: LadoCapturaEval,
): EvaluacionCaptura {
  const vacio: EvaluacionCaptura = {
    puntaje: 0,
    nitidez: 0,
    brillo: 0,
    documentoDetectado: false,
    documentoCompleto: false,
    textoLegible: false,
    lista: false,
    mensaje: 'Apunte al documento dentro del marco',
  };

  const vw = video.videoWidth;
  const vh = video.videoHeight;
  if (!vw || !vh) return vacio;

  const region =
    marco instanceof HTMLElement
      ? regionMarcoEnVideoPixels(video, marco)
      : regionDesdeMarcoRelativo(video, marco);
  if (!region) return vacio;

  const muestra = extraerMuestraMarco(video, region);
  if (!muestra) return vacio;

  const { grises, ancho, alto } = muestra;
  const brillo = media(grises);
  const contraste = desviacionEstandar(grises);
  const nitidez = varianzaLaplaciana(grises, ancho, alto);

  const brilloOk = brillo >= BRILLO_MIN && brillo <= BRILLO_MAX;
  const contrasteOk = contraste >= CONTRASTE_MIN;

  const bbox = cajaContenidoDocumento(grises, ancho, alto);
  const documentoDetectado = brilloOk && contrasteOk && bbox !== null;

  let documentoCompleto = false;
  if (bbox) {
    const coberturaAncho = (bbox.maxX - bbox.minX + 1) / ancho;
    const coberturaAlto = (bbox.maxY - bbox.minY + 1) / alto;
    const margenIzq = bbox.minX / ancho;
    const margenDer = (ancho - 1 - bbox.maxX) / ancho;
    const margenSup = bbox.minY / alto;
    const margenInf = (alto - 1 - bbox.maxY) / alto;

    const ratio = (bbox.maxX - bbox.minX + 1) / (bbox.maxY - bbox.minY + 1);
    const ratioOk =
      lado === 'pasaporte'
        ? ratio >= 0.62 && ratio <= 0.86
        : ratio >= 1.25 && ratio <= 1.95;

    documentoCompleto =
      coberturaAncho >= COBERTURA_MIN &&
      coberturaAlto >= COBERTURA_MIN &&
      margenIzq >= MARGEN_INTERNO_MIN &&
      margenDer >= MARGEN_INTERNO_MIN &&
      margenSup >= MARGEN_INTERNO_MIN &&
      margenInf >= MARGEN_INTERNO_MIN &&
      ratioOk;
  }

  const nitidezOk = nitidez >= NITIDEZ_MINIMA;
  const textoLegible = evaluarTextoLegible(grises, ancho, alto, lado, nitidez);

  const lista =
    documentoDetectado && documentoCompleto && nitidezOk && brilloOk && contrasteOk && textoLegible;

  const puntaje = calcularPuntaje({
    nitidez,
    brilloOk,
    documentoDetectado,
    documentoCompleto,
    textoLegible,
    lista,
  });

  const mensaje = mensajeEstado({
    brilloOk,
    contrasteOk,
    documentoDetectado,
    documentoCompleto,
    nitidezOk,
    textoLegible,
    lado,
    lista,
  });

  return {
    puntaje,
    nitidez,
    brillo,
    documentoDetectado,
    documentoCompleto,
    textoLegible,
    lista,
    mensaje,
  };
}

/** @deprecated Usar evaluarFrameCaptura */
export function evaluarCalidadFrame(
  video: HTMLVideoElement,
  marco: MarcoRelativo | HTMLElement,
  enfasisMrz = false,
): { puntaje: number; nitidez: number; brillo: number; lista: boolean } {
  const lado: LadoCapturaEval = enfasisMrz ? 'reverso' : 'anverso';
  const r = evaluarFrameCaptura(video, marco, lado);
  return { puntaje: r.puntaje, nitidez: r.nitidez, brillo: r.brillo, lista: r.lista };
}

function extraerMuestraMarco(
  video: HTMLVideoElement,
  region: RegionVideoPixels,
): { grises: number[]; ancho: number; alto: number } | null {
  const canvas = document.createElement('canvas');
  canvas.width = MUESTRA_ANCHO;
  canvas.height = MUESTRA_ALTO;
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  if (!ctx) return null;

  ctx.drawImage(video, region.sx, region.sy, region.sw, region.sh, 0, 0, MUESTRA_ANCHO, MUESTRA_ALTO);
  const { data } = ctx.getImageData(0, 0, MUESTRA_ANCHO, MUESTRA_ALTO);

  const grises: number[] = [];
  for (let i = 0; i < data.length; i += 4) {
    grises.push(0.299 * data[i]! + 0.587 * data[i + 1]! + 0.114 * data[i + 2]!);
  }

  return { grises, ancho: MUESTRA_ANCHO, alto: MUESTRA_ALTO };
}

function evaluarTextoLegible(
  grises: number[],
  ancho: number,
  alto: number,
  lado: LadoCapturaEval,
  nitidezGlobal: number,
): boolean {
  if (lado === 'reverso' || lado === 'pasaporte') {
    return evaluarBandaMrz(grises, ancho, alto, nitidezGlobal);
  }
  return evaluarTextoAnverso(grises, ancho, alto, nitidezGlobal);
}

function evaluarBandaMrz(
  grises: number[],
  ancho: number,
  alto: number,
  nitidezGlobal: number,
): boolean {
  const inicioY = Math.floor(alto * 0.58);
  const finY = alto - 2;
  const altoMrz = finY - inicioY;
  if (altoMrz < 12) return false;

  const nitidezMrz = varianzaLaplaciana(
    grises.slice(inicioY * ancho),
    ancho,
    altoMrz,
  );
  if (nitidezMrz < NITIDEZ_MRZ_MINIMA || nitidezGlobal < NITIDEZ_MINIMA) return false;

  const filas = 3;
  const altoFila = Math.floor(altoMrz / filas);
  let filasConTexto = 0;

  for (let f = 0; f < filas; f++) {
    const y = inicioY + f * altoFila + Math.floor(altoFila / 2);
    if (y >= alto) break;
    const transiciones = contarTransicionesFila(grises, ancho, y, 24);
    if (transiciones >= TRANSICIONES_MRZ_FILA) filasConTexto++;
  }

  return filasConTexto >= 1;
}

function evaluarTextoAnverso(
  grises: number[],
  ancho: number,
  alto: number,
  nitidezGlobal: number,
): boolean {
  if (nitidezGlobal < NITIDEZ_MINIMA) return false;

  const inicioX = Math.floor(ancho * 0.28);
  const finX = ancho - 2;
  const inicioY = Math.floor(alto * 0.15);
  const finY = Math.floor(alto * 0.85);

  let transicionesTotales = 0;
  let filas = 0;

  for (let y = inicioY; y <= finY; y += 4) {
    transicionesTotales += contarTransicionesFilaRango(grises, ancho, y, inicioX, finX, 24);
    filas++;
  }

  const mediaTransiciones = filas > 0 ? transicionesTotales / filas : 0;

  const zonaFoto = grises.slice(
    Math.floor(alto * 0.2) * ancho,
    Math.floor(alto * 0.75) * ancho,
  );
  const mitadIzq = zonaFoto.filter((_, i) => i % ancho < ancho * 0.35);
  const brilloFoto = media(mitadIzq);
  const brillo = media(grises);
  const hayFoto = brilloFoto < brillo - 12;

  return mediaTransiciones >= 8 && (hayFoto || mediaTransiciones >= 12);
}

interface Bbox {
  minX: number;
  maxX: number;
  minY: number;
  maxY: number;
}

function cajaContenidoDocumento(grises: number[], ancho: number, alto: number): Bbox | null {
  const umbral = media(grises) - desviacionEstandar(grises) * 0.15;
  let minX = ancho;
  let maxX = 0;
  let minY = alto;
  let maxY = 0;
  let puntos = 0;

  for (let y = 1; y < alto - 1; y++) {
    for (let x = 1; x < ancho - 1; x++) {
      const i = y * ancho + x;
      const gx = Math.abs(grises[i + 1]! - grises[i - 1]!);
      const gy = Math.abs(grises[i + ancho]! - grises[i - ancho]!);
      const magnitud = gx + gy;

      if (magnitud > 18 && grises[i]! < umbral + 40) {
        minX = Math.min(minX, x);
        maxX = Math.max(maxX, x);
        minY = Math.min(minY, y);
        maxY = Math.max(maxY, y);
        puntos++;
      }
    }
  }

  if (puntos < ancho * alto * 0.04) return null;
  if (maxX <= minX || maxY <= minY) return null;

  return { minX, maxX, minY, maxY };
}

function contarTransicionesFila(grises: number[], ancho: number, y: number, delta: number): number {
  return contarTransicionesFilaRango(grises, ancho, y, 2, ancho - 2, delta);
}

function contarTransicionesFilaRango(
  grises: number[],
  ancho: number,
  y: number,
  xInicio: number,
  xFin: number,
  delta: number,
): number {
  let transiciones = 0;
  const offset = y * ancho;

  for (let x = xInicio; x < xFin; x++) {
    if (Math.abs(grises[offset + x + 1]! - grises[offset + x]!) > delta) {
      transiciones++;
    }
  }

  return transiciones;
}

function calcularPuntaje(input: {
  nitidez: number;
  brilloOk: boolean;
  documentoDetectado: boolean;
  documentoCompleto: boolean;
  textoLegible: boolean;
  lista: boolean;
}): number {
  let p = 0;
  if (input.brilloOk) p += 12;
  if (input.documentoDetectado) p += 22;
  if (input.documentoCompleto) p += 28;
  p += Math.min(22, Math.round((input.nitidez / 280) * 22));
  if (input.textoLegible) p += 16;
  if (input.lista) p += 10;
  return Math.min(100, p);
}

function mensajeEstado(input: {
  brilloOk: boolean;
  contrasteOk: boolean;
  documentoDetectado: boolean;
  documentoCompleto: boolean;
  nitidezOk: boolean;
  textoLegible: boolean;
  lado: LadoCapturaEval;
  lista: boolean;
}): string {
  if (input.lista) return 'Perfecto — capturando en un momento…';
  if (!input.brilloOk) return 'Mejore la iluminación (evite sombras y reflejos)';
  if (!input.contrasteOk || !input.documentoDetectado) {
    return 'Coloque el documento dentro del marco';
  }
  if (!input.documentoCompleto) {
    return 'Acerque o aleje hasta que el documento llene el marco por completo';
  }
  if (!input.nitidezOk) return 'Mantenga la cámara quieta hasta que la imagen se vea nítida';
  if (!input.textoLegible) {
    return input.lado === 'reverso' || input.lado === 'pasaporte'
      ? 'Asegúrese de que la banda MRZ (abajo) se lea con claridad'
      : 'Enfoque los datos y la foto del anverso';
  }
  return 'Casi listo — mantenga quieto';
}

function media(valores: number[]): number {
  if (valores.length === 0) return 0;
  return valores.reduce((a, b) => a + b, 0) / valores.length;
}

function desviacionEstandar(valores: number[]): number {
  if (valores.length === 0) return 0;
  const m = media(valores);
  const varianza = valores.reduce((s, v) => s + (v - m) ** 2, 0) / valores.length;
  return Math.sqrt(varianza);
}

function varianzaLaplaciana(grises: number[], ancho: number, alto: number): number {
  if (alto < 3 || ancho < 3) return 0;

  let suma = 0;
  let cuenta = 0;

  for (let y = 1; y < alto - 1; y++) {
    for (let x = 1; x < ancho - 1; x++) {
      const i = y * ancho + x;
      const lap =
        grises[i - ancho]! +
        grises[i + ancho]! +
        grises[i - 1]! +
        grises[i + 1]! -
        4 * grises[i]!;
      suma += lap * lap;
      cuenta++;
    }
  }

  return cuenta > 0 ? suma / cuenta : 0;
}
