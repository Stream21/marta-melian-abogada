import { DocumentoIdentidadFlujo } from '@/components/documento-identidad/DocumentoIdentidadFlujo';
import type { DocumentoIdentidadResultado } from '@/components/documento-identidad/types';

export type { DocumentoIdentidadArchivos, DocumentoIdentidadResultado } from '@/components/documento-identidad/types';

interface DocumentoIdentidadEscaneoProps {
  onCompletado: (resultado: DocumentoIdentidadResultado) => void;
}

/** @deprecated Usar DocumentoIdentidadFlujo con modo="abogado" */
export function DocumentoIdentidadEscaneo({ onCompletado }: DocumentoIdentidadEscaneoProps) {
  return <DocumentoIdentidadFlujo modo="abogado" onCompletado={onCompletado} />;
}
