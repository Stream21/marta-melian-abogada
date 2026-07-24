import type { BadgeProps } from '@/components/ui/badge';

export type EstadoExpedienteValue = 'abierto' | 'cancelado' | 'archivado' | string;

export function labelEstadoExpediente(estado: EstadoExpedienteValue): string {
  switch (estado) {
    case 'abierto':
      return 'Abierto';
    case 'cancelado':
      return 'Cancelado';
    case 'archivado':
    case 'finalizado':
    case 'cerrado':
      return 'Archivado';
    default:
      return estado;
  }
}

export function variantEstadoExpediente(
  estado: EstadoExpedienteValue,
): NonNullable<BadgeProps['variant']> {
  switch (estado) {
    case 'abierto':
      return 'default';
    case 'cancelado':
      return 'warning';
    case 'archivado':
    case 'finalizado':
    case 'cerrado':
      return 'secondary';
    default:
      return 'secondary';
  }
}

/** Normaliza estados legacy del listado al filtro actual. */
export function normalizarEstadoFiltro(estado: string): 'abierto' | 'cancelado' | 'archivado' {
  if (estado === 'cancelado') return 'cancelado';
  if (estado === 'archivado' || estado === 'finalizado' || estado === 'cerrado') return 'archivado';
  return 'abierto';
}
