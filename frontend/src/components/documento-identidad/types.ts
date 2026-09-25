import type { DocumentoIdentidadExtraido, TipoEscaneoDocumentoIdentidad } from '@/api/client';
import type { TipoDocumentoIdentidadValor } from '@/lib/documento-identidad-labels';

export type ModoDocumentoIdentidad = 'cliente' | 'abogado';

export interface DocumentoIdentidadArchivos {
  tipoEscaneo: TipoEscaneoDocumentoIdentidad;
  /** Tipo que eligió el usuario (DNI / NIE / PASAPORTE), independiente del formato de escaneo. */
  tipoDocumentoElegido?: TipoDocumentoIdentidadValor;
  anverso: File;
  reverso: File | null;
}

export interface DocumentoIdentidadResultado {
  archivos: DocumentoIdentidadArchivos;
  datosExtraidos: DocumentoIdentidadExtraido;
}
