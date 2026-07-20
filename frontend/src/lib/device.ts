/** Detecta si el usuario está en un dispositivo móvil/tablet táctil. */
export function esDispositivoMovil(): boolean {
  if (typeof window === 'undefined' || typeof navigator === 'undefined') return false;

  const uaData = navigator.userAgentData as { mobile?: boolean } | undefined;
  if (uaData?.mobile === true) return true;

  const coarse = window.matchMedia('(pointer: coarse)').matches;
  const sinHover = window.matchMedia('(hover: none)').matches;
  const pantallaPequena = window.innerWidth < 900;
  const uaMovil = /Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

  return (coarse && sinHover) || (uaMovil && pantallaPequena);
}
