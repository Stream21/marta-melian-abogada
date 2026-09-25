import type {
  ClienteInput,
  DocumentoIdentidadExtraido,
  TipoEscaneoDocumentoIdentidad,
} from '@/api/client';

export const TIPOS_DOCUMENTO = ['DNI', 'NIE', 'PASAPORTE', 'OTRO'] as const;

export const CAMPOS_MRZ = [
  'tipoDocumento',
  'numDocumento',
] as const satisfies readonly (keyof ClienteInput)[];

export type CampoMrz = (typeof CAMPOS_MRZ)[number];

/** Campos MRZ bloqueados para el cliente según la extracción OCR. */
export function inferirCamposMrz(extraidos: DocumentoIdentidadExtraido): CampoMrz[] {
  if (extraidos.camposMrz?.length) {
    return extraidos.camposMrz.filter((c): c is CampoMrz =>
      (CAMPOS_MRZ as readonly string[]).includes(c),
    );
  }
  if (!extraidos.extraccionAutomatica) return [];
  return CAMPOS_MRZ.filter((campo) => {
    const valor = extraidos[campo as keyof DocumentoIdentidadExtraido];
    return valor != null && String(valor).trim() !== '';
  });
}

/** Campos MRZ bloqueados cuando el cliente ya tiene documento (corrección solo datos). */
export function camposMrzConDocumentoExistente(datos: ClienteInput): CampoMrz[] {
  return CAMPOS_MRZ.filter((campo) => {
    const valor = datos[campo];
    return valor != null && String(valor).trim() !== '';
  });
}

export const ESTADOS_CIVILES = [
  { value: 'soltero/a', label: 'Soltero/a' },
  { value: 'casado/a', label: 'Casado/a' },
  { value: 'divorciado/a', label: 'Divorciado/a' },
  { value: 'viudo/a', label: 'Viudo/a' },
  { value: 'pareja de hecho', label: 'Pareja de hecho' },
  { value: 'separado/a', label: 'Separado/a' },
  { value: 'otro', label: 'Otro' },
] as const;

/**
 * El selector de tipo no debe quedarse vacío cuando el OCR no lo reconoce: se deduce
 * del formato del número, del documento elegido por el cliente y, si no, del escaneo.
 */
export function resolverTipoDocumento(
  extraidos: DocumentoIdentidadExtraido,
  tipoEscaneo?: TipoEscaneoDocumentoIdentidad,
  tipoDocumentoElegido?: string | null,
): string {
  if ('pasaporte' === tipoEscaneo || tipoDocumentoElegido === 'PASAPORTE') return 'PASAPORTE';

  const leido = (extraidos.tipoDocumento ?? '').trim().toUpperCase();
  if ((TIPOS_DOCUMENTO as readonly string[]).includes(leido)) return leido;

  const numero = (extraidos.numDocumento ?? '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
  if (/^[XYZ]\d{7}[A-Z]$/.test(numero)) return 'NIE';
  if (/^\d{8}[A-Z]$/.test(numero)) return 'DNI';

  const elegido = (tipoDocumentoElegido ?? '').trim().toUpperCase();
  if ((TIPOS_DOCUMENTO as readonly string[]).includes(elegido)) return elegido;

  return '';
}

export function datosExtraidosAClienteInput(
  extraidos: DocumentoIdentidadExtraido,
  tipoEscaneo?: TipoEscaneoDocumentoIdentidad,
  tipoDocumentoElegido?: string | null,
): ClienteInput {
  return {
    nombre: extraidos.nombre ?? '',
    nacionalidad: extraidos.nacionalidad ?? '',
    tipoDocumento: resolverTipoDocumento(extraidos, tipoEscaneo, tipoDocumentoElegido),
    numDocumento: extraidos.numDocumento ?? '',
    fechaNacimiento: extraidos.fechaNacimiento,
    lugarNacimiento: extraidos.lugarNacimiento ?? '',
    domicilio: extraidos.domicilio ?? '',
    codigoPostal: extraidos.codigoPostal ?? '',
    ciudad: extraidos.ciudad ?? '',
    provincia: extraidos.provincia ?? '',
    nombrePadre: extraidos.nombrePadre ?? '',
    nombreMadre: extraidos.nombreMadre ?? '',
    telefono: '',
    email: '',
  };
}

/** Combina datos previos con extracción OCR sin pisar valores ya rellenos con campos vacíos. */
export function fusionarClienteInput(base: ClienteInput, extraidos: ClienteInput): ClienteInput {
  const merged = { ...base };
  (Object.keys(extraidos) as (keyof ClienteInput)[]).forEach((key) => {
    const valor = extraidos[key];
    if (valor != null && String(valor).trim() !== '') {
      merged[key] = valor;
    }
  });
  return merged;
}
