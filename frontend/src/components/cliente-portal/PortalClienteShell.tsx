import type { ReactNode } from 'react';
import type { AccesoExpedienteResponse } from '@/api/client';
import {
  PortalClienteBrandingHero,
  brandingFromAcceso,
} from '@/components/cliente-portal/PortalClienteBrandingHero';
import {
  PortalClienteRoadmap,
  roadmapFromAcceso,
} from '@/components/cliente-portal/PortalClienteRoadmap';
import { Badge } from '@/components/ui/badge';
import { useCampoEnfocadoVisible } from '@/hooks/useCampoEnfocadoVisible';
import { usePortalViewportLock } from '@/hooks/usePortalViewportLock';
import { textoVencimientoFase, calcularVencimientoFase } from '@/lib/vencimiento-fase';
import { cn } from '@/lib/utils';

interface PortalClienteShellProps {
  data: AccesoExpedienteResponse;
  children: ReactNode;
  focusMode?: boolean;
}

export function PortalClienteShell({
  data,
  children,
  focusMode = false,
}: PortalClienteShellProps) {
  usePortalViewportLock(focusMode);
  useCampoEnfocadoVisible(focusMode);

  const branding = brandingFromAcceso(data);
  const vencimiento = calcularVencimientoFase(data.fechaVencimientoFase);
  const textoVencimiento = textoVencimientoFase(data.fechaVencimientoFase);
  const showRoadmap =
    data.faseNegocio === 'contratacion' || data.faseNegocio === 'tramitacion';

  return (
    <div
      className={cn(
        'portal-focus-shell flex flex-col bg-muted/40',
        focusMode
          ? 'fixed inset-x-0 z-0 overflow-hidden'
          : 'min-h-screen',
      )}
      style={
        focusMode
          ? { top: 'var(--portal-vt, 0px)', height: 'var(--portal-vh, 100svh)' }
          : undefined
      }
    >
      <header className="shrink-0">
        <PortalClienteBrandingHero {...branding} compact dense={focusMode} />

        <div className="border-b border-border bg-card">
          <div
            className={cn(
              'mx-auto w-full max-w-3xl px-4',
              focusMode ? 'py-1.5' : 'py-2',
            )}
          >
            <div className="space-y-1">
              <p
                className={cn(
                  'text-sm font-semibold leading-snug text-foreground',
                  focusMode && 'line-clamp-2',
                )}
                title={data.servicioNombre || data.tramiteNombre}
              >
                {data.servicioNombre || data.tramiteNombre}
              </p>
              <div className="flex items-center justify-between gap-2">
                <p className="font-mono text-xs font-semibold tracking-tight text-muted-foreground">
                  {data.expedienteNumero}
                </p>
                {textoVencimiento && (
                  <Badge
                    variant={
                      vencimiento.vencido
                        ? 'destructive'
                        : vencimiento.urgente
                          ? 'warning'
                          : 'secondary'
                    }
                    className="shrink-0 whitespace-nowrap text-[11px]"
                  >
                    {textoVencimiento}
                  </Badge>
                )}
              </div>
            </div>

            {showRoadmap && (
              <div className={cn(focusMode ? 'mt-1' : 'mt-1.5')}>
                <PortalClienteRoadmap {...roadmapFromAcceso(data)} compact />
              </div>
            )}
          </div>
        </div>
      </header>

      <main
        className={cn(
          'mx-auto flex w-full max-w-3xl min-h-0 flex-1 flex-col px-4',
          focusMode
            ? 'overflow-hidden pb-[max(0.75rem,env(safe-area-inset-bottom,0.75rem))]'
            : 'overflow-hidden py-4',
        )}
      >
        <div
          className={cn(
            'flex min-h-0 flex-1 flex-col',
            !focusMode && 'panel overflow-hidden shadow-sm',
            focusMode && 'border-0 bg-transparent shadow-none',
          )}
        >
          {/* En focus mode el scroll lo gestiona cada pantalla (FocusContent / captura).
              Aquí solo delimitamos altura; overflow-y-auto en este nivel + overflow-hidden
              en un hijo flex-1 volvía a dejar el contenido sin desplazamiento. */}
          <div
            className={cn(
              'flex min-h-0 min-w-0 flex-1 flex-col',
              focusMode ? 'overflow-hidden' : 'overflow-hidden p-4 sm:p-6',
            )}
          >
            {children}
          </div>
        </div>
      </main>
    </div>
  );
}
