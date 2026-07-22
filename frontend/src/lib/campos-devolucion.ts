/** Prefijo en motivosDevolucion para marcar campos concretos de la ficha. */
export const PREFIJO_CAMPO_DEVOLUCION = 'campo:';

export const ETIQUETAS_CAMPO_CLIENTE: Record<string, string> = {
  nombre: 'Nombre completo',
  nacionalidad: 'Nacionalidad',
  tipoDocumento: 'Tipo de documento',
  numDocumento: 'Número de documento',
  fechaNacimiento: 'Fecha de nacimiento',
  lugarNacimiento: 'Lugar de nacimiento',
  estadoCivil: 'Estado civil',
  telefono: 'Teléfono',
  email: 'Correo electrónico',
  domicilio: 'Domicilio',
  codigoPostal: 'Código postal',
  ciudad: 'Ciudad',
  provincia: 'Provincia',
  nombrePadre: 'Nombre del padre',
  nombreMadre: 'Nombre de la madre',
};

export function motivoCampoDevolucion(campo: string): string {
  return `${PREFIJO_CAMPO_DEVOLUCION}${campo}`;
}

export function esMotivoCampo(motivo: string): boolean {
  return motivo.startsWith(PREFIJO_CAMPO_DEVOLUCION);
}

export function campoDesdeMotivo(motivo: string): string | null {
  return esMotivoCampo(motivo) ? motivo.slice(PREFIJO_CAMPO_DEVOLUCION.length) : null;
}

export function extraerCamposDevolucion(motivos: string[] | undefined | null): string[] {
  if (!motivos?.length) return [];
  return motivos
    .map(campoDesdeMotivo)
    .filter((c): c is string => !!c);
}

export function extraerMotivosDocumento(motivos: string[] | undefined | null): string[] {
  if (!motivos?.length) return [];
  return motivos.filter((m) => !esMotivoCampo(m));
}

export function combinarMotivosDevolucion(
  motivosDocumento: string[],
  campos: string[],
): string[] {
  const camposMotivos = campos.map(motivoCampoDevolucion);
  const base =
    campos.length > 0 && !motivosDocumento.includes('datos_personales')
      ? [...motivosDocumento, 'datos_personales']
      : motivosDocumento;
  return Array.from(new Set([...base, ...camposMotivos]));
}

export type LadoDocumentoDevolucion = 'anverso' | 'reverso' | 'completo';

export interface AnalisisDevolucionIdentidad {
  campos: string[];
  necesitaDatos: boolean;
  necesitaDocumento: boolean;
  /** Qué cara(s) debe volver a fotografiar; null si no pide documento. */
  ladoDocumento: LadoDocumentoDevolucion | null;
  documentacionAdicional: boolean;
}

/** Interpreta los motivos de devolución del abogado para guiar el portal del cliente. */
export function analizarDevolucionIdentidad(
  motivos: string[] | undefined | null,
): AnalisisDevolucionIdentidad {
  const campos = extraerCamposDevolucion(motivos);
  const doc = extraerMotivosDocumento(motivos).filter((m) => !esMotivoFirma(m));
  const pideAnverso = doc.includes('documento_anverso');
  const pideReverso = doc.includes('documento_reverso');
  const pideCompleto = doc.includes('documento_completo');
  const documentacionAdicional = doc.includes('documentacion_adicional');

  let ladoDocumento: LadoDocumentoDevolucion | null = null;
  if (pideCompleto || (pideAnverso && pideReverso)) {
    ladoDocumento = 'completo';
  } else if (pideAnverso) {
    ladoDocumento = 'anverso';
  } else if (pideReverso) {
    ladoDocumento = 'reverso';
  }

  return {
    campos,
    necesitaDatos: campos.length > 0 || doc.includes('datos_personales'),
    necesitaDocumento: ladoDocumento !== null,
    ladoDocumento,
    documentacionAdicional,
  };
}

/** Prefijo en motivosDevolucion para invalidar firmas concretas. */
export const PREFIJO_FIRMA_DEVOLUCION = 'firma:';

export function motivoFirmaDevolucion(tipo: string): string {
  return `${PREFIJO_FIRMA_DEVOLUCION}${tipo}`;
}

export function esMotivoFirma(motivo: string): boolean {
  return motivo.startsWith(PREFIJO_FIRMA_DEVOLUCION);
}

export function tipoFirmaDesdeMotivo(motivo: string): string | null {
  return esMotivoFirma(motivo) ? motivo.slice(PREFIJO_FIRMA_DEVOLUCION.length) : null;
}

export function extraerTiposFirmaDevolucion(motivos: string[] | undefined | null): string[] {
  if (!motivos?.length) return [];
  return motivos
    .map(tipoFirmaDesdeMotivo)
    .filter((t): t is string => !!t);
}
