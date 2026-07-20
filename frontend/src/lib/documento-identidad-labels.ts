import type { TipoServicioValue } from '@/lib/servicio-tipos';

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
      tarjetaIdentidadDescripcion: 'Tarjeta de identidad de extranjero (anverso y reverso)',
      tipoDocumentoCorto: 'NIE',
      numeroDocumento: 'Número de NIF',
      tipoDocumentoSelect: ['NIE', 'PASAPORTE'],
    };
  }

  return {
    tarjetaIdentidad: 'DNI / NIE',
    tarjetaIdentidadDescripcion: 'Anverso (foto) y reverso (MRZ)',
    tipoDocumentoCorto: 'DNI / NIE',
    numeroDocumento: 'Número de documento',
    tipoDocumentoSelect: ['DNI', 'NIE', 'PASAPORTE', 'OTRO'],
  };
}

export type TipoServicioContext = TipoServicioValue | string | null | undefined;
