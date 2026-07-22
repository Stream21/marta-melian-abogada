import Tesseract, { PSM, type Worker } from 'tesseract.js';
import {
  regionMarcoEnVideoPixels,
  type LadoCapturaEval,
} from '@/lib/captura-documento-calidad';
import {
  cuentaLineasMrz,
  normalizarTextoOcrMrz,
  parseMrzFromText,
  puntuacionMrzParcial,
  tieneDniNieEnTexto,
  tieneMrzLegible,
} from '@/lib/mrz-parser';

export interface OcrAnalisisMarco {
  progreso: number;
  datosLeidos: boolean;
  mensaje: string;
  analizando: boolean;
}

let workerMrz: Worker | null = null;
let workerGeneral: Worker | null = null;
let iniciandoMrz: Promise<Worker> | null = null;
let iniciandoGeneral: Promise<Worker> | null = null;
let ocrEnCurso = false;

async function obtenerWorkerMrz(): Promise<Worker> {
  if (workerMrz) return workerMrz;
  if (!iniciandoMrz) {
    iniciandoMrz = (async () => {
      const w = await Tesseract.createWorker('eng', 1, { logger: () => {} });
      await w.setParameters({
        tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<',
        tessedit_pageseg_mode: PSM.SINGLE_BLOCK,
      });
      workerMrz = w;
      return w;
    })();
  }
  return iniciandoMrz;
}

async function obtenerWorkerGeneral(): Promise<Worker> {
  if (workerGeneral) return workerGeneral;
  if (!iniciandoGeneral) {
    iniciandoGeneral = (async () => {
      const w = await Tesseract.createWorker('spa+eng', 1, { logger: () => {} });
      await w.setParameters({
        tessedit_pageseg_mode: PSM.SINGLE_BLOCK,
      });
      workerGeneral = w;
      return w;
    })();
  }
  return iniciandoGeneral;
}

export async function prepararOcrCamara(): Promise<void> {
  await Promise.all([obtenerWorkerMrz(), obtenerWorkerGeneral()]);
}

export async function liberarOcrCamara(): Promise<void> {
  ocrEnCurso = false;
  if (workerMrz) {
    await workerMrz.terminate();
    workerMrz = null;
  }
  if (workerGeneral) {
    await workerGeneral.terminate();
    workerGeneral = null;
  }
  iniciandoMrz = null;
  iniciandoGeneral = null;
}

export async function analizarMarcoConOcr(
  video: HTMLVideoElement,
  marco: HTMLElement,
  lado: LadoCapturaEval,
): Promise<OcrAnalisisMarco> {
  if (ocrEnCurso) {
    // No resetear progreso ni el contador de frames en el caller.
    return {
      progreso: -1,
      datosLeidos: false,
      mensaje: 'Leyendo documento…',
      analizando: true,
    };
  }

  const region = regionMarcoEnVideoPixels(video, marco);
  if (!region) {
    return {
      progreso: 0,
      datosLeidos: false,
      mensaje: 'Coloque el documento dentro del marco',
      analizando: false,
    };
  }

  ocrEnCurso = true;

  try {
    const canvas = canvasDesdeRegion(video, region, lado);
    const esReverso = lado === 'reverso' || lado === 'pasaporte';

    if (esReverso) {
      return await analizarReverso(canvas);
    }
    return await analizarAnverso(canvas);
  } finally {
    ocrEnCurso = false;
  }
}

async function analizarReverso(canvas: HTMLCanvasElement): Promise<OcrAnalisisMarco> {
  const worker = await obtenerWorkerMrz();
  const visual = evaluarPresenciaMrzVisual(canvas);

  const [bandaPrincipal, bandaAmplia, lineasMrz] = await Promise.all([
    ocrBandaMrz(worker, canvas, 0.36, 5, PSM.SINGLE_BLOCK),
    ocrBandaMrz(worker, canvas, 0.42, 4.5, PSM.SINGLE_LINE),
    ocrLineasMrz(worker, canvas, 0.44),
  ]);

  await worker.setParameters({ tessedit_pageseg_mode: PSM.SINGLE_BLOCK });

  const texto = [bandaPrincipal, bandaAmplia, lineasMrz].filter(Boolean).join('\n');
  const textoNorm = normalizarTextoOcrMrz(texto);
  const mrz = parseMrzFromText(textoNorm);
  const lineas = cuentaLineasMrz(textoNorm);
  const dni = tieneDniNieEnTexto(textoNorm);
  const parcial = puntuacionMrzParcial(textoNorm);
  const progresoVisual = Math.round(visual * 100);
  const progresoCombinado = Math.max(
    parcial,
    progresoVisual,
    lineas >= 2 ? 82 : lineas >= 1 ? 58 : dni ? 62 : 0,
  );

  if (mrz) {
    return {
      progreso: 100,
      datosLeidos: true,
      mensaje: `MRZ leída — ${mrz.numDocumento || mrz.nombre || 'documento detectado'}`,
      analizando: false,
    };
  }

  // Exige señal de texto MRZ (no solo presencia visual) para auto-capturar.
  if (
    (lineas >= 2 && parcial >= 40) ||
    (lineas >= 1 && parcial >= 50) ||
    (progresoCombinado >= 75 && (lineas >= 1 || parcial >= 45))
  ) {
    return {
      progreso: Math.max(82, progresoCombinado),
      datosLeidos: true,
      mensaje: 'MRZ detectada — capturando…',
      analizando: false,
    };
  }

  if (tieneMrzLegible(textoNorm) || lineas >= 2 || (lineas >= 1 && parcial >= 42)) {
    return {
      progreso: Math.max(72, progresoCombinado),
      datosLeidos: false,
      mensaje: 'Casi — alinee la banda MRZ abajo y evite reflejos',
      analizando: false,
    };
  }

  if (dni || lineas >= 1 || parcial >= 38) {
    return {
      progreso: Math.max(55, progresoCombinado),
      datosLeidos: false,
      mensaje: 'Detectando MRZ — mantenga la banda inferior nítida',
      analizando: false,
    };
  }

  if (progresoVisual >= 48 || parcial >= 22 || textoNorm.trim().length > 8) {
    return {
      progreso: Math.max(38, progresoCombinado, progresoVisual >= 48 ? 45 : 0),
      datosLeidos: false,
      mensaje: 'Banda MRZ visible — enfoque la zona inferior del documento',
      analizando: false,
    };
  }

  return {
    progreso: Math.max(10, progresoVisual >= 28 ? 22 : 10),
    datosLeidos: false,
    mensaje: 'Coloque el reverso con la banda de caracteres (MRZ) abajo en el marco',
    analizando: false,
  };
}

async function ocrBandaMrz(
  worker: Worker,
  canvas: HTMLCanvasElement,
  fraccion: number,
  escala: number,
  psm: typeof PSM.SINGLE_BLOCK | typeof PSM.SINGLE_COLUMN | typeof PSM.SINGLE_LINE,
): Promise<string> {
  const banda = recortarBandaInferior(canvas, fraccion);
  const procesada = preprocesarMrz(banda, escala);
  await worker.setParameters({ tessedit_pageseg_mode: psm });
  const { data } = await worker.recognize(procesada);
  return data.text ?? '';
}

/** OCR línea a línea sobre la banda inferior (3 franjas horizontales). */
async function ocrLineasMrz(
  worker: Worker,
  canvas: HTMLCanvasElement,
  fraccionBanda: number,
): Promise<string> {
  const banda = recortarBandaInferior(canvas, fraccionBanda);
  const lineas: string[] = [];

  await worker.setParameters({ tessedit_pageseg_mode: PSM.SINGLE_LINE });

  for (let i = 0; i < 3; i++) {
    const franja = recortarFranjaHorizontal(banda, i, 3);
    const proc = preprocesarMrz(franja, 6);
    const { data } = await worker.recognize(proc);
    const t = (data.text ?? '').trim();
    if (t.length >= 6) lineas.push(t);
  }

  return lineas.join('\n');
}

async function analizarAnverso(canvas: HTMLCanvasElement): Promise<OcrAnalisisMarco> {
  const procesada = preprocesarParaOcr(canvas, 1.8);
  const worker = await obtenerWorkerGeneral();
  const { data } = await worker.recognize(procesada);
  const texto = data.text ?? '';
  const textoNorm = normalizarTextoOcrMrz(texto);
  const lineasMrz = cuentaLineasMrz(textoNorm);
  const mrzParcial = puntuacionMrzParcial(textoNorm);

  // La MRZ del reverso contiene patrones tipo DNI (p. ej. 8 dígitos + letra).
  // Si la detectamos, el usuario está mostrando el reverso: no auto-capturar.
  if (
    lineasMrz >= 1
    || mrzParcial >= 40
    || tieneMrzLegible(textoNorm)
    || /IDESP|I<DESP|<<<</.test(textoNorm)
  ) {
    return {
      progreso: 20,
      datosLeidos: false,
      mensaje: 'Está mostrando el reverso (banda MRZ). Gire a la cara con foto',
      analizando: false,
    };
  }

  if (tieneDniNieEnTexto(texto)) {
    return {
      progreso: 100,
      datosLeidos: true,
      mensaje: 'Datos del anverso leídos correctamente',
      analizando: false,
    };
  }

  const palabrasClave = /ESPAÑA|ESPANA|DOCUMENTO|NOMBRE|APELLIDO|NACIONAL/i.test(texto);
  if (palabrasClave && texto.trim().length > 20) {
    return {
      progreso: 72,
      datosLeidos: false,
      mensaje: 'Casi — enfoque el número de documento',
      analizando: false,
    };
  }

  if (texto.trim().length > 15) {
    return {
      progreso: 40,
      datosLeidos: false,
      mensaje: 'Leyendo anverso… mantenga el documento quieto',
      analizando: false,
    };
  }

  return {
    progreso: 12,
    datosLeidos: false,
    mensaje: 'Coloque el anverso (cara con foto) dentro del marco',
    analizando: false,
  };
}

/** Heurística visual: ¿hay texto tipo MRZ en el tercio inferior? (0–1) */
function evaluarPresenciaMrzVisual(canvas: HTMLCanvasElement): number {
  const banda = recortarBandaInferior(canvas, 0.42);
  const w = Math.min(320, banda.width);
  const h = Math.max(1, Math.round(banda.height * (w / banda.width)));

  const tmp = document.createElement('canvas');
  tmp.width = w;
  tmp.height = h;
  const ctx = tmp.getContext('2d', { willReadFrequently: true });
  if (!ctx) return 0;

  ctx.drawImage(banda, 0, 0, w, h);
  const { data } = ctx.getImageData(0, 0, w, h);

  const grises: number[] = [];
  for (let i = 0; i < data.length; i += 4) {
    grises.push(0.299 * data[i]! + 0.587 * data[i + 1]! + 0.114 * data[i + 2]!);
  }

  const inicioY = Math.floor(h * 0.08);
  const finY = h - 2;
  const altoMrz = finY - inicioY;
  if (altoMrz < 8) return 0;

  const filas = 3;
  const altoFila = Math.floor(altoMrz / filas);
  let filasConTexto = 0;
  let transicionesMax = 0;

  for (let f = 0; f < filas; f++) {
    const y = inicioY + f * altoFila + Math.floor(altoFila / 2);
    if (y >= h) break;
    const trans = contarTransicionesFila(grises, w, y, 22);
    transicionesMax = Math.max(transicionesMax, trans);
    if (trans >= 9) filasConTexto++;
  }

  const nitidez = varianzaLaplaciana(grises, w, h);
  let score = 0;
  if (filasConTexto >= 1) score += 0.3;
  if (filasConTexto >= 2) score += 0.35;
  if (filasConTexto >= 3) score += 0.2;
  if (transicionesMax >= 14) score += 0.15;
  if (nitidez >= 90) score += 0.1;

  return Math.min(1, score);
}

function contarTransicionesFila(grises: number[], ancho: number, y: number, delta: number): number {
  let transiciones = 0;
  const offset = y * ancho;
  for (let x = 2; x < ancho - 2; x++) {
    if (Math.abs(grises[offset + x + 1]! - grises[offset + x]!) > delta) {
      transiciones++;
    }
  }
  return transiciones;
}

function varianzaLaplaciana(grises: number[], ancho: number, alto: number): number {
  if (alto < 3 || ancho < 3) return 0;
  let suma = 0;
  let cuenta = 0;
  for (let y = 1; y < alto - 1; y++) {
    for (let x = 1; x < ancho - 1; x++) {
      const i = y * ancho + x;
      const lap =
        grises[i - ancho]! + grises[i + ancho]! + grises[i - 1]! + grises[i + 1]! - 4 * grises[i]!;
      suma += lap * lap;
      cuenta++;
    }
  }
  return cuenta > 0 ? suma / cuenta : 0;
}

function canvasDesdeRegion(
  video: HTMLVideoElement,
  region: { sx: number; sy: number; sw: number; sh: number },
  lado: LadoCapturaEval = 'anverso',
): HTMLCanvasElement {
  const maxAncho = lado === 'reverso' || lado === 'pasaporte' ? 1280 : 960;
  const escala = Math.min(1, maxAncho / region.sw);
  const w = Math.round(region.sw * escala);
  const h = Math.round(region.sh * escala);

  const canvas = document.createElement('canvas');
  canvas.width = w;
  canvas.height = h;
  const ctx = canvas.getContext('2d')!;
  ctx.drawImage(video, region.sx, region.sy, region.sw, region.sh, 0, 0, w, h);
  return canvas;
}

function recortarBandaInferior(canvas: HTMLCanvasElement, fraccion: number): HTMLCanvasElement {
  const h = Math.max(1, Math.round(canvas.height * fraccion));
  const y = canvas.height - h;
  const out = document.createElement('canvas');
  out.width = canvas.width;
  out.height = h;
  const ctx = out.getContext('2d')!;
  ctx.drawImage(canvas, 0, y, canvas.width, h, 0, 0, canvas.width, h);
  return out;
}

function recortarFranjaHorizontal(canvas: HTMLCanvasElement, indice: number, total: number): HTMLCanvasElement {
  const h = Math.max(1, Math.floor(canvas.height / total));
  const y = indice * h;
  const out = document.createElement('canvas');
  out.width = canvas.width;
  out.height = h;
  const ctx = out.getContext('2d')!;
  ctx.drawImage(canvas, 0, y, canvas.width, h, 0, 0, canvas.width, h);
  return out;
}

function preprocesarMrz(canvas: HTMLCanvasElement, escala: number): HTMLCanvasElement {
  const w = Math.round(canvas.width * escala);
  const h = Math.round(canvas.height * escala);

  const tmp = document.createElement('canvas');
  tmp.width = canvas.width;
  tmp.height = canvas.height;
  const tctx = tmp.getContext('2d', { willReadFrequently: true })!;
  tctx.drawImage(canvas, 0, 0);

  const img = tctx.getImageData(0, 0, canvas.width, canvas.height);
  const { data, width, height } = img;

  let min = 255;
  let max = 0;
  const grises: number[] = new Array(width * height);

  for (let i = 0, p = 0; i < data.length; i += 4, p++) {
    const g = 0.299 * data[i]! + 0.587 * data[i + 1]! + 0.114 * data[i + 2]!;
    grises[p] = g;
    min = Math.min(min, g);
    max = Math.max(max, g);
  }

  const range = Math.max(1, max - min);
  for (let p = 0; p < grises.length; p++) {
    const stretched = ((grises[p]! - min) / range) * 255;
    const bin = stretched > 108 ? 255 : stretched < 78 ? 0 : stretched;
    const idx = p * 4;
    data[idx] = bin;
    data[idx + 1] = bin;
    data[idx + 2] = bin;
  }

  tctx.putImageData(img, 0, 0);

  const out = document.createElement('canvas');
  out.width = w;
  out.height = h;
  const ctx = out.getContext('2d')!;
  ctx.imageSmoothingEnabled = false;
  ctx.drawImage(tmp, 0, 0, w, h);
  return out;
}

function preprocesarParaOcr(canvas: HTMLCanvasElement, escala: number): HTMLCanvasElement {
  const w = Math.round(canvas.width * escala);
  const h = Math.round(canvas.height * escala);
  const out = document.createElement('canvas');
  out.width = w;
  out.height = h;
  const ctx = out.getContext('2d')!;
  ctx.filter = 'grayscale(1) contrast(1.35)';
  ctx.drawImage(canvas, 0, 0, w, h);
  return out;
}
