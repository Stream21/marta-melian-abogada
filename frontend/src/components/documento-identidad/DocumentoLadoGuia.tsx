import { useId } from 'react';
import { cn } from '@/lib/utils';
import { ID1_ASPECT_RATIO, PASAPORTE_ANCHO_MM, PASAPORTE_ALTO_MM } from '@/lib/documento-id1';

type LadoGuia = 'anverso' | 'reverso' | 'pasaporte';

interface DocumentoLadoGuiaProps {
  lado: LadoGuia;
  className?: string;
}

const TITULO: Record<LadoGuia, string> = {
  anverso: 'Cara frontal · con foto',
  reverso: 'Cara trasera · banda inferior',
  pasaporte: 'Página de datos del pasaporte',
};

/**
 * Guía visual tipo KYC: muestra qué cara escanear.
 * En NIE/DNI compara anverso/reverso y resalta solo la cara activa.
 */
export function DocumentoLadoGuia({ lado, className }: DocumentoLadoGuiaProps) {
  return (
    <div className={cn('flex flex-col items-center gap-3', className)}>
      {lado === 'pasaporte' ? (
        <GuiaPasaporte />
      ) : (
        <GuiaTarjetaDosCaras activa={lado} />
      )}
      <p className="max-w-[18rem] text-center text-sm font-medium text-foreground">{TITULO[lado]}</p>
    </div>
  );
}

function GuiaTarjetaDosCaras({ activa }: { activa: 'anverso' | 'reverso' }) {
  return (
    <div className="flex w-full items-end justify-center gap-3 px-1" role="img" aria-label={TITULO[activa]}>
      <CaraMini
        tipo="anverso"
        activa={activa === 'anverso'}
        etiqueta="Anverso"
      />
      <CaraMini
        tipo="reverso"
        activa={activa === 'reverso'}
        etiqueta="Reverso"
      />
    </div>
  );
}

function CaraMini({
  tipo,
  activa,
  etiqueta,
}: {
  tipo: 'anverso' | 'reverso';
  activa: boolean;
  etiqueta: string;
}) {
  const uid = useId().replace(/:/g, '');
  return (
    <div
      className={cn(
        'flex w-[46%] max-w-[9.5rem] flex-col items-center gap-2 transition-opacity',
        activa ? 'opacity-100' : 'opacity-40',
      )}
    >
      <div
        className={cn(
          'relative w-full overflow-hidden rounded-xl bg-muted/40 p-1.5',
          activa && 'ring-2 ring-primary ring-offset-2 ring-offset-card',
        )}
      >
        {tipo === 'anverso' ? <SvgAnverso uid={uid} /> : <SvgReverso uid={uid} />}
        {activa && (
          <span className="absolute -right-0.5 -top-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground shadow-sm">
            ✓
          </span>
        )}
      </div>
      <span
        className={cn(
          'text-[11px] font-semibold uppercase tracking-wide',
          activa ? 'text-primary' : 'text-muted-foreground',
        )}
      >
        {etiqueta}
      </span>
    </div>
  );
}

function SvgAnverso({ uid }: { uid: string }) {
  const w = 200;
  const h = Math.round(w / ID1_ASPECT_RATIO);
  const shadow = `shadow-${uid}`;
  const gloss = `gloss-${uid}`;

  return (
    <svg viewBox={`0 0 ${w} ${h}`} className="h-auto w-full" aria-hidden>
      <defs>
        <linearGradient id={gloss} x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="hsl(var(--card))" stopOpacity="1" />
          <stop offset="55%" stopColor="hsl(var(--muted))" stopOpacity="1" />
          <stop offset="100%" stopColor="hsl(var(--card))" stopOpacity="1" />
        </linearGradient>
        <filter id={shadow} x="-8%" y="-8%" width="116%" height="124%">
          <feDropShadow dx="0" dy="2" stdDeviation="2.5" floodOpacity="0.14" />
        </filter>
      </defs>
      <rect
        x="4"
        y="4"
        width={w - 8}
        height={h - 8}
        rx="10"
        fill={`url(#${gloss})`}
        stroke="hsl(var(--border))"
        strokeWidth="1.5"
        filter={`url(#${shadow})`}
      />
      {/* Franja superior institucional */}
      <rect x="4" y="4" width={w - 8} height="14" rx="10" fill="hsl(var(--primary) / 0.88)" />
      <rect x="4" y="12" width={w - 8} height="6" fill="hsl(var(--primary) / 0.88)" />
      {/* Foto */}
      <rect x="14" y="28" width="48" height="58" rx="5" fill="hsl(var(--muted-foreground) / 0.18)" />
      <circle cx="38" cy="48" r="11" fill="hsl(var(--muted-foreground) / 0.35)" />
      <ellipse cx="38" cy="72" rx="16" ry="10" fill="hsl(var(--muted-foreground) / 0.35)" />
      {/* Datos */}
      <rect x="72" y="30" width="100" height="7" rx="3.5" fill="hsl(var(--muted-foreground) / 0.28)" />
      <rect x="72" y="44" width="88" height="6" rx="3" fill="hsl(var(--muted-foreground) / 0.2)" />
      <rect x="72" y="56" width="72" height="6" rx="3" fill="hsl(var(--muted-foreground) / 0.2)" />
      <rect x="72" y="68" width="56" height="6" rx="3" fill="hsl(var(--muted-foreground) / 0.2)" />
      {/* Chip */}
      <rect x="72" y="82" width="22" height="16" rx="2.5" fill="hsl(var(--primary) / 0.35)" />
    </svg>
  );
}

function SvgReverso({ uid }: { uid: string }) {
  const w = 200;
  const h = Math.round(w / ID1_ASPECT_RATIO);
  const shadow = `shadow-${uid}`;
  const gloss = `gloss-${uid}`;

  return (
    <svg viewBox={`0 0 ${w} ${h}`} className="h-auto w-full" aria-hidden>
      <defs>
        <linearGradient id={gloss} x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor="hsl(var(--card))" />
          <stop offset="100%" stopColor="hsl(var(--muted))" />
        </linearGradient>
        <filter id={shadow} x="-8%" y="-8%" width="116%" height="124%">
          <feDropShadow dx="0" dy="2" stdDeviation="2.5" floodOpacity="0.14" />
        </filter>
      </defs>
      <rect
        x="4"
        y="4"
        width={w - 8}
        height={h - 8}
        rx="10"
        fill={`url(#${gloss})`}
        stroke="hsl(var(--border))"
        strokeWidth="1.5"
        filter={`url(#${shadow})`}
      />
      <rect x="16" y="18" width="110" height="6" rx="3" fill="hsl(var(--muted-foreground) / 0.22)" />
      <rect x="16" y="30" width="88" height="5" rx="2.5" fill="hsl(var(--muted-foreground) / 0.16)" />
      <rect x="16" y="42" width="64" height="28" rx="4" fill="hsl(var(--muted-foreground) / 0.12)" />
      {/* MRZ — zona clave del reverso */}
      <rect
        x="10"
        y={h - 42}
        width={w - 20}
        height="30"
        rx="4"
        fill="hsl(var(--primary) / 0.1)"
        stroke="hsl(var(--primary) / 0.45)"
        strokeWidth="1.25"
      />
      <rect x="16" y={h - 34} width={w - 32} height="4" rx="2" fill="hsl(var(--foreground) / 0.55)" />
      <rect x="16" y={h - 26} width={w - 32} height="4" rx="2" fill="hsl(var(--foreground) / 0.55)" />
      <rect x="16" y={h - 18} width={w - 40} height="4" rx="2" fill="hsl(var(--foreground) / 0.55)" />
    </svg>
  );
}

function GuiaPasaporte() {
  const uid = useId().replace(/:/g, '');
  const w = 148;
  const h = Math.round((w / PASAPORTE_ANCHO_MM) * PASAPORTE_ALTO_MM);
  const shadow = `shadow-${uid}`;
  const page = `page-${uid}`;

  return (
    <div
      className="relative w-full max-w-[8.5rem] overflow-hidden rounded-xl bg-muted/40 p-2 ring-2 ring-primary ring-offset-2 ring-offset-card"
      role="img"
      aria-label={TITULO.pasaporte}
    >
      <svg viewBox={`0 0 ${w} ${h}`} className="h-auto w-full" aria-hidden>
        <defs>
          <linearGradient id={page} x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stopColor="hsl(var(--muted))" />
            <stop offset="18%" stopColor="hsl(var(--card))" />
            <stop offset="100%" stopColor="hsl(var(--card))" />
          </linearGradient>
          <filter id={shadow} x="-10%" y="-6%" width="120%" height="112%">
            <feDropShadow dx="0" dy="2" stdDeviation="2.5" floodOpacity="0.14" />
          </filter>
        </defs>
        <rect
          x="8"
          y="6"
          width={w - 16}
          height={h - 12}
          rx="8"
          fill={`url(#${page})`}
          stroke="hsl(var(--border))"
          strokeWidth="1.5"
          filter={`url(#${shadow})`}
        />
        <rect x="8" y="6" width="12" height={h - 12} rx="8" fill="hsl(var(--muted-foreground) / 0.12)" />
        <rect x="28" y="22" width="42" height="52" rx="4" fill="hsl(var(--muted-foreground) / 0.18)" />
        <circle cx="49" cy="40" r="9" fill="hsl(var(--muted-foreground) / 0.35)" />
        <ellipse cx="49" cy="60" rx="13" ry="8" fill="hsl(var(--muted-foreground) / 0.35)" />
        <rect x="78" y="26" width="48" height="6" rx="3" fill="hsl(var(--muted-foreground) / 0.28)" />
        <rect x="78" y="38" width="40" height="5" rx="2.5" fill="hsl(var(--muted-foreground) / 0.18)" />
        <rect x="28" y="86" width="98" height="5" rx="2.5" fill="hsl(var(--muted-foreground) / 0.18)" />
        <rect x="28" y="98" width="86" height="5" rx="2.5" fill="hsl(var(--muted-foreground) / 0.16)" />
        <rect
          x="24"
          y={h - 40}
          width={w - 40}
          height="24"
          rx="3"
          fill="hsl(var(--primary) / 0.1)"
          stroke="hsl(var(--primary) / 0.4)"
          strokeWidth="1"
        />
        <rect x="28" y={h - 32} width={w - 48} height="3.5" rx="1.5" fill="hsl(var(--foreground) / 0.5)" />
        <rect x="28" y={h - 24} width={w - 52} height="3.5" rx="1.5" fill="hsl(var(--foreground) / 0.5)" />
      </svg>
      <span className="absolute -right-0.5 -top-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground shadow-sm">
        ✓
      </span>
    </div>
  );
}
