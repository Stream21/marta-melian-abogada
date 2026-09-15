export interface VencimientoFaseInfo {
  diasRestantes: number | null;
  vencido: boolean;
  urgente: boolean;
  fechaFormateada: string | null;
}

/** Diferencia en días de calendario (hoy = 0), sin sesgo por hora del día. */
export function diasCalendarioHasta(fecha: string | null | undefined): number | null {
  if (!fecha) return null;
  const raw = fecha.slice(0, 10);
  const parts = raw.split('-').map(Number);
  if (parts.length !== 3 || parts.some((n) => !Number.isFinite(n))) return null;
  const [y, m, d] = parts;
  const venc = new Date(y, m - 1, d);
  const ahora = new Date();
  const hoy = new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate());
  return Math.round((venc.getTime() - hoy.getTime()) / 86_400_000);
}

export function calcularVencimientoFase(fecha: string | null | undefined): VencimientoFaseInfo {
  const dias = diasCalendarioHasta(fecha);
  if (dias === null || !fecha) {
    return {
      diasRestantes: null,
      vencido: false,
      urgente: false,
      fechaFormateada: null,
    };
  }

  const venc = new Date(`${fecha.slice(0, 10)}T12:00:00`);

  return {
    diasRestantes: dias,
    vencido: dias < 0,
    urgente: dias >= 0 && dias <= 7,
    fechaFormateada: venc.toLocaleDateString('es-ES'),
  };
}

export function textoVencimientoFase(fecha: string | null | undefined): string | null {
  const info = calcularVencimientoFase(fecha);
  if (!info.fechaFormateada || info.diasRestantes === null) return null;
  if (info.vencido) {
    const n = Math.abs(info.diasRestantes);
    return n === 0 ? 'Vencido hoy' : `Vencido hace ${n} día${n === 1 ? '' : 's'}`;
  }
  if (info.diasRestantes === 0) {
    return 'Vence hoy';
  }
  if (info.urgente) {
    const n = info.diasRestantes;
    return `Vence en ${n} día${n === 1 ? '' : 's'}`;
  }
  return `Vence el ${info.fechaFormateada}`;
}
