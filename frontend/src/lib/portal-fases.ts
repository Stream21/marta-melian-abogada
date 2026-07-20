import type { FaseNegocio } from '@/api/client';

export const PORTAL_FASES: Array<{ fase: FaseNegocio; label: string; orden: number }> = [
  { fase: 'contratacion', label: 'Contratación', orden: 1 },
  { fase: 'requerimientos', label: 'Requerimientos', orden: 2 },
  { fase: 'tramitacion', label: 'Tramitación', orden: 3 },
  { fase: 'resolucion', label: 'Resolución', orden: 4 },
];

export function indiceFaseNegocio(fase: FaseNegocio): number {
  return PORTAL_FASES.findIndex((f) => f.fase === fase);
}

export function labelFaseNegocio(fase: FaseNegocio): string {
  return PORTAL_FASES.find((f) => f.fase === fase)?.label ?? fase;
}
