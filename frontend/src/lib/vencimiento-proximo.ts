import type { ExpedienteResponse } from '@/api/client';
import { calcularVencimientoFase } from '@/lib/vencimiento-fase';

export type VencimientoActivoTipo = 'fase' | 'cuota';

export interface VencimientoActivo {
  tipo: VencimientoActivoTipo;
  label: string;
  /** YYYY-MM-DD */
  fecha: string;
}

/**
 * Plazos activos del expediente: plazo de fase + cuotas no cobradas.
 * Ordenados por fecha ascendente (el primero = el más próximo / más urgente si está vencido).
 */
export function listarVencimientosActivos(exp: ExpedienteResponse): VencimientoActivo[] {
  const items: VencimientoActivo[] = [];

  const fase = exp.fechaVencimientoFase?.trim();
  if (fase) {
    items.push({
      tipo: 'fase',
      label: 'Plazo de fase',
      fecha: fase.slice(0, 10),
    });
  }

  for (const cobro of exp.resumenCobros?.items ?? []) {
    if (cobro.estado === 'pagado') continue;
    const fecha = cobro.fecha?.trim();
    if (!fecha) continue;
    items.push({
      tipo: 'cuota',
      label: cobro.nombre,
      fecha: fecha.slice(0, 10),
    });
  }

  return items.sort((a, b) => a.fecha.localeCompare(b.fecha));
}

export function proximoVencimiento(exp: ExpedienteResponse): VencimientoActivo | null {
  return listarVencimientosActivos(exp)[0] ?? null;
}

export function tienePlazoVencido(exp: ExpedienteResponse): boolean {
  return listarVencimientosActivos(exp).some((v) => calcularVencimientoFase(v.fecha).vencido);
}

export function tienePlazoUrgente(exp: ExpedienteResponse): boolean {
  return listarVencimientosActivos(exp).some((v) => calcularVencimientoFase(v.fecha).urgente);
}
