import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { Shield } from 'lucide-react';
import type { AccesoExpedienteResponse } from '@/api/client';
import {
  PortalClienteBrandingHero,
  brandingFromAcceso,
} from '@/components/cliente-portal/PortalClienteBrandingHero';
import {
  PortalClienteRoadmap,
  roadmapFromAcceso,
} from '@/components/cliente-portal/PortalClienteRoadmap';
import { esDispositivoMovil } from '@/lib/device';

interface PortalClienteShellProps {
  data: AccesoExpedienteResponse;
  children: ReactNode;
}

export function PortalClienteShell({ data, children }: PortalClienteShellProps) {
  const branding = brandingFromAcceso(data);
  const [esMovil, setEsMovil] = useState(false);
  const nombre =
    data.clienteNombre?.trim() ||
    data.clienteDatos?.nombre?.trim() ||
    'Su expediente';

  useEffect(() => {
    setEsMovil(esDispositivoMovil());
  }, []);

  return (
    <div className="min-h-screen bg-muted/40 pb-10">
      <PortalClienteBrandingHero {...branding} compact={esMovil} />

      <div className="border-b border-border bg-card">
        <div className="mx-auto max-w-2xl px-4 py-4">
          <p className="break-words text-lg font-semibold leading-snug text-foreground">{nombre}</p>
          <div className="mt-1.5 space-y-0.5 text-sm text-muted-foreground sm:flex sm:flex-wrap sm:items-baseline sm:gap-x-2 sm:space-y-0">
            <p className="shrink-0 font-mono text-[13px] tracking-tight">{data.expedienteNumero}</p>
            <p className="hidden sm:inline" aria-hidden>
              ·
            </p>
            <p className="break-words leading-snug">{data.tramiteNombre}</p>
          </div>
        </div>
      </div>

      <main className="mx-auto w-full max-w-2xl space-y-4 px-4 pt-5">
        <PortalClienteRoadmap {...roadmapFromAcceso(data)} />

        <div className="panel overflow-hidden shadow-sm">
          <div className="p-4 sm:p-6">{children}</div>
        </div>

        <footer className="flex items-center justify-center gap-2 px-2 text-center text-[11px] text-muted-foreground">
          <Shield className="h-3.5 w-3.5 shrink-0 text-primary/60" />
          <span>
            Conexión segura · Sus datos están protegidos conforme a la normativa de protección de
            datos
          </span>
        </footer>
      </main>
    </div>
  );
}
