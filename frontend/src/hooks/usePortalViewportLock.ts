import { useEffect } from 'react';

import { esCampoEditable } from '@/lib/campo-enfocado';

/**
 * Bloquea el scroll del documento y sincroniza --portal-vh / --portal-vt con visualViewport
 * (Safari iOS: barras del navegador, safe areas y teclado en pantalla).
 */
export function usePortalViewportLock(active: boolean) {
  useEffect(() => {
    if (!active) return;

    const root = document.documentElement;
    const body = document.body;

    const escribiendo = () => esCampoEditable(document.activeElement);

    const syncViewport = () => {
      const vv = window.visualViewport;
      root.style.setProperty('--portal-vh', `${Math.round(vv?.height ?? window.innerHeight)}px`);
      // Con el teclado abierto Safari puede desplazar el viewport visual: el contenedor fijo
      // debe seguirlo o queda medio tapado.
      root.style.setProperty('--portal-vt', `${Math.round(vv?.offsetTop ?? 0)}px`);
    };

    // Mientras se escribe es Safari quien desplaza la página para dejar el campo a la vista:
    // forzar scrollTo(0,0) ahí se lo arrebata y el input desaparece bajo el teclado.
    const onViewportScroll = () => {
      syncViewport();
      if (0 !== window.scrollY && !escribiendo()) {
        window.scrollTo(0, 0);
      }
    };

    syncViewport();
    onViewportScroll();

    const vv = window.visualViewport;
    vv?.addEventListener('resize', syncViewport);
    vv?.addEventListener('scroll', onViewportScroll);
    window.addEventListener('resize', syncViewport);
    window.addEventListener('orientationchange', syncViewport);

    root.classList.add('portal-focus-lock');
    body.style.overflow = 'hidden';

    return () => {
      vv?.removeEventListener('resize', syncViewport);
      vv?.removeEventListener('scroll', onViewportScroll);
      window.removeEventListener('resize', syncViewport);
      window.removeEventListener('orientationchange', syncViewport);
      root.classList.remove('portal-focus-lock');
      root.style.removeProperty('--portal-vh');
      root.style.removeProperty('--portal-vt');
      body.style.overflow = '';
    };
  }, [active]);
}
