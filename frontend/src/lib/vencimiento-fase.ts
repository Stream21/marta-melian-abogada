export interface VencimientoFaseInfo {
  diasRestantes: number | null;
  vencido: boolean;
  urgente: boolean;
  fechaFormateada: string | null;
}

export function calcularVencimientoFase(fecha: string | null | undefined): VencimientoFaseInfo {
  if (!fecha) {
    return {
      diasRestantes: null,
      vencido: false,
      urgente: false,
      fechaFormateada: null,
    };
  }

  const venc = new Date(fecha + 'T23:59:59');
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  const dias = Math.ceil((venc.getTime() - hoy.getTime()) / 86400000);

  return {
    diasRestantes: dias,
    vencido: dias < 0,
    urgente: dias >= 0 && dias <= 7,
    fechaFormateada: venc.toLocaleDateString('es-ES'),
  };
}

export function textoVencimientoFase(fecha: string | null | undefined): string | null {
  const info = calcularVencimientoFase(fecha);
  if (!info.fechaFormateada) return null;
  if (info.vencido && info.diasRestantes !== null) {
    return `Vencido hace ${Math.abs(info.diasRestantes)} día(s)`;
  }
  if (info.urgente && info.diasRestantes !== null) {
    return `Vence en ${info.diasRestantes} día(s)`;
  }
  return `Vence el ${info.fechaFormateada}`;
}
