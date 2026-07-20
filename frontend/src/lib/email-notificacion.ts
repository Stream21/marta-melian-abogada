const CANAL_LABELS: Record<string, string> = {
  whatsapp: 'WhatsApp',
  email: 'correo',
};

export function etiquetaCanal(canal: string): string {
  return CANAL_LABELS[canal] ?? canal;
}

export function mensajeCanalesEnviados(
  canales: string[],
  bandejaUrl?: string | null,
): string | null {
  const ok = canales.filter((c) => !c.endsWith('_error'));
  const err = canales.filter((c) => c.endsWith('_error'));

  if (ok.length > 0) {
    const partes = ok.map((canal) => {
      if (canal === 'email' && bandejaUrl) {
        return `correo (desarrollo: bandeja en ${bandejaUrl})`;
      }
      return etiquetaCanal(canal);
    });

    return `Enviado por: ${partes.join(', ')}`;
  }

  if (err.length > 0) {
    const fallos = err.map((c) => etiquetaCanal(c.replace(/_error$/, '')));
    return `No se pudo enviar por: ${fallos.join(', ')}. Revise Mailpit (http://localhost:8025), MAILER_DSN en .env o los datos del cliente.`;
  }

  return null;
}

export const NOTIFICACION_ALTA_KEY = 'bufete_notificacion_alta';

export function guardarNotificacionAlta(canales: string[], bandejaUrl?: string | null): void {
  const mensaje = mensajeCanalesEnviados(canales, bandejaUrl);
  if (!mensaje) return;
  sessionStorage.setItem(
    NOTIFICACION_ALTA_KEY,
    JSON.stringify({ mensaje, esError: canales.some((c) => c.endsWith('_error')) }),
  );
}

export function consumirNotificacionAlta(): { mensaje: string; esError: boolean } | null {
  const raw = sessionStorage.getItem(NOTIFICACION_ALTA_KEY);
  if (!raw) return null;
  sessionStorage.removeItem(NOTIFICACION_ALTA_KEY);
  try {
    return JSON.parse(raw) as { mensaje: string; esError: boolean };
  } catch {
    return null;
  }
}
