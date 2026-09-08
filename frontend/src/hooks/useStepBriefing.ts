import { useCallback, useEffect, useState } from 'react';

const claveSesion = (stepKey: string) => `portal-briefing-${stepKey}`;

function leerDescartado(stepKey: string): boolean {
  try {
    return sessionStorage.getItem(claveSesion(stepKey)) === '1';
  } catch {
    return false;
  }
}

type UseStepBriefingOptions = {
  /**
   * Si false, solo recuerda el cierre en memoria (no sessionStorage).
   * Útil en capturas anverso/reverso: deben verse en cada flujo nuevo.
   */
  persist?: boolean;
};

/**
 * Muestra el briefing de un paso una sola vez. Con persist (por defecto) lo
 * guarda en sessionStorage para no repetirlo al navegar atrás en la misma sesión.
 */
export function useStepBriefing(
  stepKey: string,
  enabled = true,
  options: UseStepBriefingOptions = {},
) {
  const persist = options.persist !== false;
  const [descartados, setDescartados] = useState<string[]>([]);

  const visto =
    stepKey === '' ||
    descartados.includes(stepKey) ||
    (persist && leerDescartado(stepKey));

  const dismiss = useCallback(() => {
    if (stepKey === '') return;
    if (persist) {
      try {
        sessionStorage.setItem(claveSesion(stepKey), '1');
      } catch {
        // Sesión sin almacenamiento: basta con el estado en memoria.
      }
    }
    setDescartados((prev) => (prev.includes(stepKey) ? prev : [...prev, stepKey]));
  }, [stepKey, persist]);

  const reset = useCallback((keys?: string[]) => {
    setDescartados((prev) => {
      if (!keys || keys.length === 0) return [];
      return prev.filter((k) => !keys.includes(k));
    });
    if (!persist || !keys) return;
    for (const key of keys) {
      try {
        sessionStorage.removeItem(claveSesion(key));
      } catch {
        // ignore
      }
    }
  }, [persist]);

  return { open: enabled && !visto, dismiss, reset };
}

export function useWelcomeBriefing(token: string, enabled = true) {
  const storageKey = `portal-welcome-${token}`;
  const [open, setOpen] = useState(
    () => enabled && typeof sessionStorage !== 'undefined' && sessionStorage.getItem(storageKey) !== '1',
  );

  useEffect(() => {
    if (!enabled) setOpen(false);
  }, [enabled]);

  const dismiss = useCallback(() => {
    sessionStorage.setItem(storageKey, '1');
    setOpen(false);
  }, [storageKey]);

  return { open: enabled && open, dismiss };
}
