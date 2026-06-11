const ALPHA_THRESHOLD = 8;
const TRIM_PADDING = 20;

export interface SignaturePoint {
  x: number;
  y: number;
}

export function isCanvasBlank(canvas: HTMLCanvasElement): boolean {
  const context = canvas.getContext('2d', { willReadFrequently: true });
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
  const context = source.getContext('2d', { willReadFrequently: true });
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

  const trimmedContext = trimmed.getContext('2d');
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

export function setupSignatureCanvas(
  canvas: HTMLCanvasElement,
  width: number,
  height: number,
): CanvasRenderingContext2D | null {
  const ratio = window.devicePixelRatio || 1;
  canvas.width = Math.floor(width * ratio);
  canvas.height = Math.floor(height * ratio);
  canvas.style.width = `${width}px`;
  canvas.style.height = `${height}px`;

  const context = canvas.getContext('2d');
  if (!context) return null;

  context.setTransform(ratio, 0, 0, ratio, 0, 0);
  context.clearRect(0, 0, width, height);
  context.lineCap = 'round';
  context.lineJoin = 'round';
  context.lineWidth = 2.2;
  context.strokeStyle = '#1f2937';

  return context;
}

export function clearSignatureCanvas(
  canvas: HTMLCanvasElement,
  width: number,
  height: number,
): CanvasRenderingContext2D | null {
  return setupSignatureCanvas(canvas, width, height);
}
