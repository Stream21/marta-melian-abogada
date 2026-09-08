import type { FaseNegocio } from '@/api/client';

export const PORTAL_FASES: Array<{ fase: FaseNegocio; label: string; orden: number }> = [
  { fase: 'contratacion', label: 'Contratación', orden: 1 },
  { fase: 'requerimientos', label: 'Requerimientos', orden: 2 },
  { fase: 'tramitacion', label: 'Tramitación', orden: 3 },
  { fase: 'resolucion', label: 'Resolución', orden: 4 },
];

/** Las 3 subfases dentro de la fase contratación. */
export const SUBFASES_CONTRATACION = [
  {
    codigo: 'datos_cliente',
    label: 'Identidad y datos',
    orden: 1,
    descripcion: 'Documento de identidad y datos personales',
  },
  {
    codigo: 'firmas',
    label: 'Firmas legales',
    orden: 2,
    descripcion: 'Hoja de encargo, designación y RGPD',
  },
  {
    codigo: 'pago',
    label: 'Pago inicial',
    orden: 3,
    descripcion: 'Pago según el método acordado con el despacho',
  },
] as const;

export const SUBFASES_CONTRATACION_TOTAL = SUBFASES_CONTRATACION.length;

export type SubfaseContratacionCodigo = (typeof SUBFASES_CONTRATACION)[number]['codigo'];

export function indiceFaseNegocio(fase: FaseNegocio): number {
  return PORTAL_FASES.findIndex((f) => f.fase === fase);
}

export function labelFaseNegocio(fase: FaseNegocio): string {
  return PORTAL_FASES.find((f) => f.fase === fase)?.label ?? fase;
}

export function labelSubfaseContratacion(codigo: string): string {
  return SUBFASES_CONTRATACION.find((s) => s.codigo === codigo)?.label ?? codigo;
}
