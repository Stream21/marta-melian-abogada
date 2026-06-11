import type { DocumentoIdentidadExtraido, TipoEscaneoDocumentoIdentidad } from '@/api/client';

export type ModoDocumentoIdentidad = 'cliente' | 'abogado';

export interface DocumentoIdentidadArchivos {
  tipoEscaneo: TipoEscaneoDocumentoIdentidad;
  anverso: File;
  reverso: File | null;
}

export interface DocumentoIdentidadResultado {
  archivos: DocumentoIdentidadArchivos;
  datosExtraidos: DocumentoIdentidadExtraido;
}
