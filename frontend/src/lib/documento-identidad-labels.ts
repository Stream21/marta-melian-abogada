import type { TipoServicioValue } from '@/lib/servicio-tipos';

/** Claves técnicas internas (API/BD). Etiquetas de UI: Delantera / Trasera. */
export type LadoDocumentoTecnico = 'anverso' | 'reverso';

export const LADO_DOCUMENTO_ETIQUETA: Record<LadoDocumentoTecnico, string> = {
  anverso: 'Delantera',
  reverso: 'Trasera',
};

export function etiquetaLadoDocumento(lado: LadoDocumentoTecnico): string {
  return LADO_DOCUMENTO_ETIQUETA[lado];
}

export function esExpedienteExtranjeria(tipoServicio?: string | null): boolean {
  return tipoServicio === 'extranjeria_nacionalidad';
}

export interface LabelsDocumentoIdentidad {
  tarjetaIdentidad: string;
  tarjetaIdentidadDescripcion: string;
  tipoDocumentoCorto: string;
  numeroDocumento: string;
  tipoDocumentoSelect: string[];
}

export function labelsDocumentoIdentidad(tipoServicio?: string | null): LabelsDocumentoIdentidad {
  const extranjeria = esExpedienteExtranjeria(tipoServicio);

  if (extranjeria) {
    return {
      tarjetaIdentidad: 'NIE',
      tarjetaIdentidadDescripcion: 'Tarjeta de identidad de extranjero (delantera y trasera)',
      tipoDocumentoCorto: 'NIE',
      numeroDocumento: 'Número de NIF',
      tipoDocumentoSelect: ['NIE', 'PASAPORTE'],
    };
  }

  return {
    tarjetaIdentidad: 'DNI / NIE',
    tarjetaIdentidadDescripcion: 'Delantera (foto) y trasera (MRZ)',
    tipoDocumentoCorto: 'DNI / NIE',
    numeroDocumento: 'Número de documento',
    tipoDocumentoSelect: ['DNI', 'NIE', 'PASAPORTE', 'OTRO'],
  };
}

export type TipoServicioContext = TipoServicioValue | string | null | undefined;
