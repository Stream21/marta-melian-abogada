import { useEffect, useState } from 'react';
import type { AccesoExpedienteResponse } from '@/api/client';
import { apiAbsoluteUrl } from '@/api/apiBase';
import { cn } from '@/lib/utils';

const LOGO_FALLBACK = '/logo.png';

interface PortalClienteBrandingHeroProps {
  logoUrl?: string | null;
  nombreFirma?: string | null;
  compact?: boolean;
  /** Aún más bajo en flujos focus (captura, etc.). */
  dense?: boolean;
  /** Inline logo for compact header row (no full-bleed navy bar). */
  inline?: boolean;
}

export function PortalClienteBrandingHero({
  logoUrl,
  nombreFirma,
  compact = false,
  dense = false,
  inline = false,
}: PortalClienteBrandingHeroProps) {
  const [logoSrc, setLogoSrc] = useState(() =>
    logoUrl ? apiAbsoluteUrl(logoUrl) : LOGO_FALLBACK,
  );

  useEffect(() => {
    setLogoSrc(logoUrl ? apiAbsoluteUrl(logoUrl) : LOGO_FALLBACK);
  }, [logoUrl]);

  const alt = nombreFirma?.trim() || 'Bufete Melián';

  if (inline) {
    return (
      <img
        src={logoSrc}
        alt={alt}
        className="h-8 w-auto max-w-[120px] object-contain"
        onError={() => {
          if (logoSrc !== LOGO_FALLBACK) setLogoSrc(LOGO_FALLBACK);
        }}
      />
    );
  }

  return (
    <div
      className={cn(
        'bg-primary px-4',
        dense ? 'py-2' : compact ? 'py-3' : 'px-6 py-4',
        !dense && !compact && 'px-6',
      )}
    >
      <div className="mx-auto flex max-w-2xl justify-center">
        <img
          src={logoSrc}
          alt={alt}
          className={
            dense
              ? 'h-9 w-auto max-w-[140px] object-contain'
              : compact
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

export function brandingFromAcceso(
  data: Pick<
    AccesoExpedienteResponse,
    'despachoLogoUrl' | 'despachoNombreFirma' | 'despachoSubtitulo'
  >,
) {
  return {
    logoUrl: data.despachoLogoUrl,
    nombreFirma: data.despachoNombreFirma,
  };
}
