import type { TipoDocumentoRequerido } from '@/api/client';

export function esDocumentoConjunto(tipo: string, maxImagenes: number): boolean {
  return tipo === 'conjunto' && maxImagenes > 1;
}

export function documentoUploadLimiteLabel(tipo: TipoDocumentoRequerido | string, maxImagenes: number): string {
  if (esDocumentoConjunto(tipo, maxImagenes)) {
    return `Hasta ${maxImagenes} archivos`;
  }
  return '1 archivo';
}

export function documentoUploadLimiteDetalle(tipo: TipoDocumentoRequerido | string, maxImagenes: number): string {
  if (esDocumentoConjunto(tipo, maxImagenes)) {
    return `Puede adjuntar hasta ${maxImagenes} archivos (imagen, PDF, Word, etc.). Cada archivo se convertirá a PDF por separado.`;
  }
  return 'Adjunte una imagen, PDF, Word u otro documento compatible. Se guardará como PDF.';
}

/** Tipos MIME y extensiones aceptados en la subida de requerimientos. */
export const DOCUMENTO_UPLOAD_ACCEPT =
  'image/jpeg,image/png,image/webp,image/gif,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text,application/rtf,.doc,.docx,.odt,.rtf';
