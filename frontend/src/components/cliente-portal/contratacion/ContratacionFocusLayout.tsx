import type { ReactNode } from 'react';
import { PortalScrollArea } from '@/components/cliente-portal/PortalScrollArea';
import { cn } from '@/lib/utils';

/**
 * Pantallas de contenido (pago, firmas, docs…): este bloque es el dueño del scroll.
 * Incluye una pista superior clara; se puede desactivar si otra capa (p. ej. PDF) guía el scroll.
 */
export function ContratacionFocusContent({
  children,
  className,
  showScrollHint = true,
  scrollHintLabel,
}: {
  children: ReactNode;
  className?: string;
  showScrollHint?: boolean;
  scrollHintLabel?: string;
}) {
  return (
    <PortalScrollArea
      className={className}
      showHint={showScrollHint}
      {...(scrollHintLabel ? { etiqueta: scrollHintLabel } : {})}
    >
      <div className="space-y-4 pb-4 pt-3">{children}</div>
    </PortalScrollArea>
  );
}

/**
 * Pantallas que llenan el viewport (captura de identidad): el hijo gestiona su propio scroll.
 */
export function ContratacionFocusFill({
  children,
  className,
}: {
  children: ReactNode;
  className?: string;
}) {
  return (
    <div className={cn('flex min-h-0 flex-1 flex-col overflow-hidden', className)}>{children}</div>
  );
}
