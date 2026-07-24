const ALPHA_THRESHOLD = 8;
const TRIM_PADDING = 20;

/** Same options everywhere — mismatched getContext attrs return null. */
const CONTEXT_OPTIONS: CanvasRenderingContext2DSettings = {
  willReadFrequently: true,
};

export interface SignaturePoint {
  x: number;
  y: number;
}

function getDrawingContext(canvas: HTMLCanvasElement): CanvasRenderingContext2D | null {
  return canvas.getContext('2d', CONTEXT_OPTIONS);
}

export function isCanvasBlank(canvas: HTMLCanvasElement): boolean {
  const context = getDrawingContext(canvas);
  if (!context) return true;

  const { width, height } = canvas;
  if (width === 0 || height === 0) return true;

  const pixels = context.getImageData(0, 0, width, height).data;
  for (let index = 3; index < pixels.length; index += 4) {
    if (pixels[index] > ALPHA_THRESHOLD) {
      return false;
    }
  }

  return true;
}

export function trimTransparentCanvas(source: HTMLCanvasElement): HTMLCanvasElement {
  const context = getDrawingContext(source);
  if (!context) return source;

  const { width, height } = source;
  const pixels = context.getImageData(0, 0, width, height).data;

  let minX = width;
  let minY = height;
  let maxX = 0;
  let maxY = 0;

  for (let y = 0; y < height; y += 1) {
    for (let x = 0; x < width; x += 1) {
      const alpha = pixels[(y * width + x) * 4 + 3];
      if (alpha > ALPHA_THRESHOLD) {
        minX = Math.min(minX, x);
        minY = Math.min(minY, y);
        maxX = Math.max(maxX, x);
        maxY = Math.max(maxY, y);
      }
    }
  }

  if (minX > maxX || minY > maxY) {
    return source;
  }

  minX = Math.max(0, minX - TRIM_PADDING);
  minY = Math.max(0, minY - TRIM_PADDING);
  maxX = Math.min(width - 1, maxX + TRIM_PADDING);
  maxY = Math.min(height - 1, maxY + TRIM_PADDING);

  const trimmedWidth = maxX - minX + 1;
  const trimmedHeight = maxY - minY + 1;
  const trimmed = document.createElement('canvas');
  trimmed.width = trimmedWidth;
  trimmed.height = trimmedHeight;

  const trimmedContext = trimmed.getContext('2d', CONTEXT_OPTIONS);
  if (!trimmedContext) return source;

  trimmedContext.drawImage(
    source,
    minX,
    minY,
    trimmedWidth,
    trimmedHeight,
    0,
    0,
    trimmedWidth,
    trimmedHeight,
  );

  return trimmed;
}

export async function canvasToTransparentPngFile(
  canvas: HTMLCanvasElement,
  filename: string,
): Promise<File> {
  const trimmed = trimTransparentCanvas(canvas);
  const blob = await new Promise<Blob | null>((resolve) => {
    trimmed.toBlob(resolve, 'image/png');
  });

  if (!blob) {
    throw new Error('No se pudo generar la imagen de la firma.');
  }

  return new File([blob], filename, { type: 'image/png' });
}

export function applySignatureStrokeStyle(context: CanvasRenderingContext2D): void {
  context.lineCap = 'round';
  context.lineJoin = 'round';
  context.lineWidth = 2.2;
  context.strokeStyle = '#1f2937';
}

/**
 * Configura el buffer con DPR. El tamaño visual lo controla CSS (no style inline):
 * asignar style.width en móvil desincroniza el hit-testing del touch.
 */
export function setupSignatureCanvas(
  canvas: HTMLCanvasElement,
  cssWidth: number,
  cssHeight: number,
): CanvasRenderingContext2D | null {
  const ratio = window.devicePixelRatio || 1;
  const nextW = Math.max(1, Math.floor(cssWidth * ratio));
  const nextH = Math.max(1, Math.floor(cssHeight * ratio));

  if (canvas.width !== nextW || canvas.height !== nextH) {
    canvas.width = nextW;
    canvas.height = nextH;
  }

  const context = getDrawingContext(canvas);
  if (!context) return null;

  context.setTransform(ratio, 0, 0, ratio, 0, 0);
  context.clearRect(0, 0, cssWidth, cssHeight);
  applySignatureStrokeStyle(context);

  return context;
}

/** Borra el trazo sin recrear el buffer (reescribir width/height rompe iOS/Safari). */
export function clearSignatureCanvas(
  canvas: HTMLCanvasElement,
  cssWidth: number,
  cssHeight: number,
): CanvasRenderingContext2D | null {
  const context = getDrawingContext(canvas);
  if (!context || canvas.width === 0 || canvas.height === 0) {
    return setupSignatureCanvas(canvas, cssWidth, cssHeight);
  }

  const ratio = window.devicePixelRatio || 1;
  context.setTransform(1, 0, 0, 1, 0, 0);
  context.clearRect(0, 0, canvas.width, canvas.height);
  context.setTransform(ratio, 0, 0, ratio, 0, 0);
  applySignatureStrokeStyle(context);

  return context;
}
