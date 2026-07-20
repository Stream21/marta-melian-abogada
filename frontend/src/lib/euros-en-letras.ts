const UNIDADES = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
const DIEZ_A_DIECINUEVE = [
  'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
];
const DECENAS = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
const CENTENAS = [
  '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
];

function enteroEnLetras(n: number): string {
  if (n === 0) return 'CERO';
  if (n < 10) return UNIDADES[n];
  if (n < 20) return DIEZ_A_DIECINUEVE[n - 10];
  if (n < 30) {
    const veinti: Record<number, string> = {
      20: 'VEINTE',
      21: 'VEINTIUNO',
      22: 'VEINTIDÓS',
      23: 'VEINTITRÉS',
      24: 'VEINTICUATRO',
      25: 'VEINTICINCO',
      26: 'VEINTISÉIS',
      27: 'VEINTISIETE',
      28: 'VEINTIOCHO',
      29: 'VEINTINUEVE',
    };
    return veinti[n] ?? DECENAS[Math.floor(n / 10)];
  }
  if (n < 100) {
    const decena = Math.floor(n / 10);
    const unidad = n % 10;
    return DECENAS[decena] + (unidad > 0 ? ` Y ${UNIDADES[unidad]}` : '');
  }
  if (n === 100) return 'CIEN';
  if (n < 1000) {
    const centena = Math.floor(n / 100);
    const resto = n % 100;
    return CENTENAS[centena] + (resto > 0 ? ` ${enteroEnLetras(resto)}` : '');
  }
  if (n < 2000) {
    const resto = n % 1000;
    return 'MIL' + (resto > 0 ? ` ${enteroEnLetras(resto)}` : '');
  }
  if (n < 1_000_000) {
    const miles = Math.floor(n / 1000);
    const resto = n % 1000;
    const milesTexto = miles === 1 ? 'MIL' : `${enteroEnLetras(miles)} MIL`;
    return milesTexto + (resto > 0 ? ` ${enteroEnLetras(resto)}` : '');
  }
  if (n < 2_000_000) {
    const resto = n % 1_000_000;
    return 'UN MILLÓN' + (resto > 0 ? ` ${enteroEnLetras(resto)}` : '');
  }
  if (n < 1_000_000_000) {
    const millones = Math.floor(n / 1_000_000);
    const resto = n % 1_000_000;
    return `${enteroEnLetras(millones)} MILLONES` + (resto > 0 ? ` ${enteroEnLetras(resto)}` : '');
  }
  throw new Error('Importe demasiado grande para convertir a letras.');
}

/** Convierte un importe en euros a texto en mayúsculas para contratos. */
export function eurosEnLetras(importe: number): string {
  if (importe < 0) throw new Error('El importe no puede ser negativo.');

  const redondeado = Math.round(importe * 100) / 100;
  const parteEntera = Math.floor(redondeado);
  const centimos = Math.round((redondeado - parteEntera) * 100);

  if (parteEntera === 0 && centimos === 0) return 'CERO EUROS';

  const texto = enteroEnLetras(parteEntera);
  let resultado = parteEntera === 1 ? 'UN EURO' : `${texto} EUROS`;

  if (centimos > 0) {
    const centimosTexto = centimos === 1 ? 'UN' : enteroEnLetras(centimos);
    resultado += ` CON ${centimosTexto} ${centimos === 1 ? 'CÉNTIMO' : 'CÉNTIMOS'}`;
  }

  return resultado;
}
