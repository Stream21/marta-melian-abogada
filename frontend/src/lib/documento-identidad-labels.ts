import type { TipoServicioValue } from '@/lib/servicio-tipos';
import type { TipoEscaneoDocumentoIdentidad } from '@/api/client';

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

export type TipoDocumentoIdentidadValor = 'DNI' | 'NIE' | 'PASAPORTE';

export interface OpcionSeleccionDocumento {
  valor: TipoDocumentoIdentidadValor;
  label: string;
  descripcionCliente: string;
  tipoEscaneo: TipoEscaneoDocumentoIdentidad;
}

export interface LabelsDocumentoIdentidad {
  numeroDocumento: string;
  tipoDocumentoSelect: string[];
  /** Opciones del paso «Elija su documento» (una tarjeta por tipo). */
  opcionesSeleccion: OpcionSeleccionDocumento[];
}

const OPCIONES_SELECCION: OpcionSeleccionDocumento[] = [
  {
    valor: 'DNI',
    label: 'DNI',
    descripcionCliente: 'Documento nacional · delantera y trasera · 2 fotos',
    tipoEscaneo: 'dni_nie',
  },
  {
    valor: 'NIE',
    label: 'NIE',
    descripcionCliente: 'Tarjeta de identidad de extranjero · 2 fotos',
    tipoEscaneo: 'dni_nie',
  },
  {
    valor: 'PASAPORTE',
    label: 'Pasaporte',
    descripcionCliente: 'Página interior con sus datos · 1 foto',
    tipoEscaneo: 'pasaporte',
  },
];

export function labelsDocumentoIdentidad(tipoServicio?: string | null): LabelsDocumentoIdentidad {
  const extranjeria = esExpedienteExtranjeria(tipoServicio);

  return {
    numeroDocumento: 'Número de documento',
    tipoDocumentoSelect: extranjeria
      ? ['NIE', 'DNI', 'PASAPORTE']
      : ['DNI', 'NIE', 'PASAPORTE', 'OTRO'],
    opcionesSeleccion: OPCIONES_SELECCION,
  };
}

export function etiquetaTipoDocumento(valor: TipoDocumentoIdentidadValor | string | null | undefined): string {
  if (valor === 'PASAPORTE' || valor === 'pasaporte') return 'Pasaporte';
  if (valor === 'NIE') return 'NIE';
  if (valor === 'DNI') return 'DNI';
  return 'Documento';
}

export type TipoServicioContext = TipoServicioValue | string | null | undefined;
