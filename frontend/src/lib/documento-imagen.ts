/** Normaliza orientación EXIF y rotación manual del documento antes de OCR/subida. */

export async function rotacionInicialSugerida(file: File): Promise<number> {
  const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
  const vertical = esDocumentoVertical(bitmap.width, bitmap.height);
  bitmap.close();

  return vertical ? 90 : 0;
}

export function esDocumentoVertical(width: number, height: number): boolean {
  return height > width * 1.05;
}

export async function renderDocumentoImage(
  file: File,
  rotationDeg: number,
): Promise<{ previewUrl: string; normalizedFile: File }> {
  const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
  const rot = ((rotationDeg % 360) + 360) % 360;
  const swap = rot === 90 || rot === 270;
  const canvasW = swap ? bitmap.height : bitmap.width;
  const canvasH = swap ? bitmap.width : bitmap.height;

  const canvas = document.createElement('canvas');
  canvas.width = canvasW;
  canvas.height = canvasH;
  const ctx = canvas.getContext('2d');
  if (!ctx) {
    bitmap.close();
    throw new Error('No se pudo procesar la imagen.');
  }

  ctx.translate(canvasW / 2, canvasH / 2);
  ctx.rotate((rot * Math.PI) / 180);
  ctx.drawImage(bitmap, -bitmap.width / 2, -bitmap.height / 2);
  bitmap.close();

  const blob = await new Promise<Blob>((resolve, reject) => {
    canvas.toBlob(
      (b) => (b ? resolve(b) : reject(new Error('No se pudo generar la imagen.'))),
      'image/jpeg',
      0.92,
    );
  });

  const nombre = file.name.replace(/\.[^.]+$/, '') + '.jpg';
  const normalizedFile = new File([blob], nombre, { type: 'image/jpeg', lastModified: Date.now() });

  return { previewUrl: URL.createObjectURL(blob), normalizedFile };
}
