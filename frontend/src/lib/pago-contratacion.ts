import type { CalendarioCuotaResponse, PlanPago } from '@/api/client';

type ImportePagoSource = {
  honorariosAcordados: number;
  planPago: PlanPago;
  numCuotas: number;
  importePagoInicial?: number;
  calendarioPago?: CalendarioCuotaResponse[] | null;
  calendarioProyectado?: CalendarioCuotaResponse[] | null;
};

export function getImportePagoInicial(data: ImportePagoSource): number {
  if (data.importePagoInicial != null && data.importePagoInicial > 0) {
    return data.importePagoInicial;
  }

  const calendario = data.calendarioPago ?? data.calendarioProyectado;
  if (calendario?.length) {
    return calendario[0].importe;
  }

  if (data.planPago === 'unico') {
    return data.honorariosAcordados;
  }

  const cuotas = Math.max(2, Math.min(4, data.numCuotas));
  const centimosTotal = Math.round(data.honorariosAcordados * 100);
  const base = Math.floor(centimosTotal / cuotas);
  const resto = centimosTotal - base * cuotas;

  return (base + (resto > 0 ? 1 : 0)) / 100;
}

export function formatEuros(importe: number): string {
  return `${importe.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €`;
}
