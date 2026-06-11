import type { ClienteInput, DocumentoIdentidadExtraido } from '@/api/client';

export const ESTADOS_CIVILES = [
  { value: 'soltero/a', label: 'Soltero/a' },
  { value: 'casado/a', label: 'Casado/a' },
  { value: 'divorciado/a', label: 'Divorciado/a' },
  { value: 'viudo/a', label: 'Viudo/a' },
  { value: 'pareja de hecho', label: 'Pareja de hecho' },
  { value: 'separado/a', label: 'Separado/a' },
  { value: 'otro', label: 'Otro' },
] as const;

export function datosExtraidosAClienteInput(extraidos: DocumentoIdentidadExtraido): ClienteInput {
  return {
    nombre: extraidos.nombre,
    nacionalidad: extraidos.nacionalidad,
    tipoDocumento: extraidos.tipoDocumento,
    numDocumento: extraidos.numDocumento,
    fechaNacimiento: extraidos.fechaNacimiento,
    lugarNacimiento: extraidos.lugarNacimiento,
    domicilio: extraidos.domicilio ?? '',
    codigoPostal: extraidos.codigoPostal ?? '',
    ciudad: extraidos.ciudad ?? '',
    provincia: extraidos.provincia ?? '',
    nombrePadre: extraidos.nombrePadre ?? '',
    nombreMadre: extraidos.nombreMadre ?? '',
  };
}
