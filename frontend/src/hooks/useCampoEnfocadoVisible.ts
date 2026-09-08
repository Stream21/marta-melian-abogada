import { useEffect } from 'react';

import { esCampoEditable } from '@/lib/campo-enfocado';

/**
 * En móvil el teclado se come más de la mitad de la pantalla y el campo enfocado queda
 * debajo. Como el portal vive en un contenedor con scroll propio, el desplazamiento
 * automático del navegador no basta: hay que centrar el campo dentro de ese contenedor.
 */
export function useCampoEnfocadoVisible(active: boolean) {
  useEffect(() => {
    if (!active) return;

    let temporizador: number | undefined;

    const centrar = () => {
      const el = document.activeElement;
      if (!esCampoEditable(el)) return;
      el.scrollIntoView({ block: 'center', behavior: 'smooth' });
    };

    const programarCentrado = () => {
      window.clearTimeout(temporizador);
      // Esperar a que el teclado termine de aparecer y el visualViewport se estabilice.
      temporizador = window.setTimeout(centrar, 320);
    };

    const alEnfocar = (event: FocusEvent) => {
      if (!esCampoEditable(event.target as Element | null)) return;
      programarCentrado();
    };

    document.addEventListener('focusin', alEnfocar);
    window.visualViewport?.addEventListener('resize', programarCentrado);

    return () => {
      window.clearTimeout(temporizador);
      document.removeEventListener('focusin', alEnfocar);
      window.visualViewport?.removeEventListener('resize', programarCentrado);
    };
  }, [active]);
}
