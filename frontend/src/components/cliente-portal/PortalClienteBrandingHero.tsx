import { useEffect, useState } from 'react';
import type { AccesoExpedienteResponse } from '@/api/client';

const LOGO_FALLBACK = '/logo.png';

interface PortalClienteBrandingHeroProps {
  logoUrl?: string | null;
  nombreFirma?: string | null;
  compact?: boolean;
}

export function PortalClienteBrandingHero({
  logoUrl,
  nombreFirma,
  compact = false,
}: PortalClienteBrandingHeroProps) {
  const [logoSrc, setLogoSrc] = useState(logoUrl ?? LOGO_FALLBACK);

  useEffect(() => {
    setLogoSrc(logoUrl ?? LOGO_FALLBACK);
  }, [logoUrl]);

  const alt = nombreFirma?.trim() || 'Bufete Melián';

  return (
    <div className={compact ? 'bg-primary px-4 py-3' : 'bg-primary px-6 py-4'}>
      <div className="mx-auto flex max-w-2xl justify-center">
        <img
          src={logoSrc}
          alt={alt}
          className={
            compact
              ? 'h-11 w-auto max-w-[160px] object-contain'
              : 'h-12 w-auto max-w-[180px] object-contain sm:h-14'
          }
          onError={() => {
            if (logoSrc !== LOGO_FALLBACK) setLogoSrc(LOGO_FALLBACK);
          }}
        />
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
  };
}
