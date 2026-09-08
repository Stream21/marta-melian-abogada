/**
 * Teléfono en un único campo: el cliente escribe su número y el backend lo normaliza
 * a E.164 (asume prefijo español si no se indica otro).
 */

/** Deja solo dígitos, con un «+» inicial opcional para números extranjeros. */
export function sanitizarTelefono(valor: string): string {
  const empiezaConMas = valor.trimStart().startsWith('+');
  const digitos = valor.replace(/\D/g, '');

  return empiezaConMas ? `+${digitos}` : digitos;
}

/**
 * Los móviles españoles se muestran en formato local (sin +34); los demás conservan su
 * prefijo internacional porque sin él no se pueden identificar.
 */
export function telefonoParaMostrar(valor: string): string {
  const limpio = sanitizarTelefono(valor);
  const nacional = limpio.replace(/^\+?34/, '');

  return /^[67]\d{8}$/.test(nacional) ? nacional : limpio;
}

/** Mismo criterio que TelefonoNormalizer en el backend, para avisar antes de enviar. */
export function telefonoValido(valor: string): boolean {
  const limpio = sanitizarTelefono(valor);
  const digitos = limpio.replace('+', '');

  if (limpio.startsWith('+')) {
    return /^[1-9]\d{6,14}$/.test(digitos);
  }
  if (/^[67]\d{8}$/.test(digitos)) {
    return true;
  }

  return /^[1-9]\d{9,14}$/.test(digitos);
}
