import { useEffect, useState } from 'react';
import type { AccesoExpedienteResponse } from '@/api/client';

const LOGO_FALLBACK = '/logo.png';
const FIRMA_FALLBACK = 'Bufete Melián';
const LEMA_FALLBACK = 'Servicios Jurídicos Profesionales';

interface PortalClienteBrandingHeroProps {
  logoUrl?: string | null;
  nombreFirma?: string | null;
  subtitulo?: string | null;
  compact?: boolean;
}

export function PortalClienteBrandingHero({
  logoUrl,
  nombreFirma,
  subtitulo,
  compact = false,
}: PortalClienteBrandingHeroProps) {
  const [logoSrc, setLogoSrc] = useState(logoUrl ?? LOGO_FALLBACK);

  useEffect(() => {
    setLogoSrc(logoUrl ?? LOGO_FALLBACK);
  }, [logoUrl]);

  const firma = nombreFirma?.trim() || FIRMA_FALLBACK;
  const lema = subtitulo?.trim() || LEMA_FALLBACK;

  return (
    <div
      className={
        compact
          ? 'bg-primary px-4 py-5 text-center'
          : 'bg-primary px-6 py-8 text-center sm:py-10'
      }
    >
      <div className="mx-auto flex max-w-2xl flex-col items-center">
        <img
          src={logoSrc}
          alt={firma}
          className={
            compact
              ? 'h-16 w-auto max-w-[200px] object-contain drop-shadow-sm'
              : 'h-20 w-auto max-w-[220px] object-contain drop-shadow-sm sm:h-24'
          }
          onError={() => {
            if (logoSrc !== LOGO_FALLBACK) setLogoSrc(LOGO_FALLBACK);
          }}
        />
        <div className="mt-4 w-full max-w-sm">
          <div className="mx-auto mb-3 h-px w-10 bg-primary-foreground/25" />
          <p className="text-sm font-semibold tracking-wide text-primary-foreground sm:text-base">
            {firma}
          </p>
          <p className="mt-1.5 text-[10px] font-medium uppercase tracking-[0.2em] text-primary-foreground/60 sm:text-[11px]">
            {lema}
          </p>
        </div>
      </div>
    </div>
  );
}

export function brandingFromAcceso(data: Pick<
  AccesoExpedienteResponse,
  'despachoLogoUrl' | 'despachoNombreFirma' | 'despachoSubtitulo'
>) {
  return {
    logoUrl: data.despachoLogoUrl,
    nombreFirma: data.despachoNombreFirma,
    subtitulo: data.despachoSubtitulo,
  };
}
