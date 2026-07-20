import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { ID1_ASPECT_RATIO, PASAPORTE_ANCHO_MM, PASAPORTE_ALTO_MM } from '@/lib/documento-id1';

type LadoGuia = 'anverso' | 'reverso' | 'pasaporte';

const CARD_W = 320;
const CARD_H = Math.round(CARD_W / ID1_ASPECT_RATIO);

const PASS_W = 200;
const PASS_H = Math.round((PASS_W / PASAPORTE_ANCHO_MM) * PASAPORTE_ALTO_MM);

const PALETTE = {
  card: '#f1f5f9',
  cardEdge: '#94a3b8',
  ink: '#475569',
  inkLight: '#cbd5e1',
  photo: '#e2e8f0',
  photoSilhouette: '#94a3b8',
  chip: '#fbbf24',
  zone: '#10b981',
  zoneFill: 'rgb(16 185 129 / 0.2)',
  mrz: '#334155',
};

interface DocumentoLadoGuiaProps {
  lado: LadoGuia;
  className?: string;
  mostrarZonasLeidas?: boolean;
}

const SUBTITULO: Record<LadoGuia, string> = {
  anverso: 'Cara con foto, en horizontal',
  reverso: 'Reverso con la banda inferior completa',
  pasaporte: 'Página interior del pasaporte, en vertical',
};

export function DocumentoLadoGuia({ lado, className, mostrarZonasLeidas = false }: DocumentoLadoGuiaProps) {
  return (
    <div className={cn('flex flex-col items-center gap-3', className)}>
      <div className="rounded-2xl bg-gradient-to-b from-muted/40 to-muted/10 p-4 shadow-sm ring-1 ring-border/60">
        {lado === 'anverso' && <GuiaAnverso mostrarZonasLeidas={mostrarZonasLeidas} />}
        {lado === 'reverso' && <GuiaReverso mostrarZonasLeidas={mostrarZonasLeidas} />}
        {lado === 'pasaporte' && <GuiaPasaporte mostrarZonasLeidas={mostrarZonasLeidas} />}
      </div>
      <p className="max-w-[18rem] text-center text-sm text-muted-foreground">{SUBTITULO[lado]}</p>
    </div>
  );
}

function IlustracionTarjeta({ children }: { children: ReactNode }) {
  return (
    <svg
      viewBox={`0 0 ${CARD_W} ${CARD_H}`}
      className="h-auto w-full max-w-[17.5rem]"
      aria-hidden
      role="img"
    >
      <defs>
        <filter id="card-shadow" x="-4%" y="-4%" width="108%" height="112%">
          <feDropShadow dx="0" dy="2" stdDeviation="3" floodOpacity="0.12" />
        </filter>
      </defs>
      <rect
        x="8"
        y="8"
        width={CARD_W - 16}
        height={CARD_H - 16}
        rx="14"
        fill={PALETTE.card}
        stroke={PALETTE.cardEdge}
        strokeWidth="2"
        filter="url(#card-shadow)"
      />
      {children}
    </svg>
  );
}

function IlustracionPasaporte({ children }: { children: ReactNode }) {
  return (
    <svg
      viewBox={`0 0 ${PASS_W} ${PASS_H}`}
      className="h-auto w-full max-w-[9.5rem]"
      aria-hidden
      role="img"
    >
      <defs>
        <filter id="passport-shadow" x="-6%" y="-3%" width="112%" height="108%">
          <feDropShadow dx="0" dy="3" stdDeviation="4" floodOpacity="0.14" />
        </filter>
        <linearGradient id="passport-spine" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stopColor="#cbd5e1" />
          <stop offset="100%" stopColor="#f1f5f9" stopOpacity="0" />
        </linearGradient>
      </defs>
      <rect
        x="10"
        y="6"
        width={PASS_W - 20}
        height={PASS_H - 12}
        rx="8"
        fill={PALETTE.card}
        stroke={PALETTE.cardEdge}
        strokeWidth="2"
        filter="url(#passport-shadow)"
      />
      <rect x="10" y="6" width="14" height={PASS_H - 12} rx="8" fill="url(#passport-spine)" opacity="0.55" />
      {children}
    </svg>
  );
}

function ZonaLeida({
  x,
  y,
  width,
  height,
  label,
}: {
  x: number;
  y: number;
  width: number;
  height: number;
  label?: string;
}) {
  return (
    <g>
      <rect
        x={x}
        y={y}
        width={width}
        height={height}
        rx="6"
        fill={PALETTE.zoneFill}
        stroke={PALETTE.zone}
        strokeWidth="2"
        strokeDasharray="6 3"
      />
      {label && (
        <g>
          <circle cx={x + 10} cy={y + 10} r="9" fill={PALETTE.zone} />
          <text x={x + 10} y={y + 14} textAnchor="middle" fontSize="10" fontWeight="700" fill="white">
            {label}
          </text>
        </g>
      )}
    </g>
  );
}

function SiluetaFoto({ x, y, w, h }: { x: number; y: number; w: number; h: number }) {
  const cx = x + w / 2;
  const headR = w * 0.22;
  return (
    <g fill={PALETTE.photoSilhouette} opacity="0.55">
      <rect x={x} y={y} width={w} height={h} rx="6" fill={PALETTE.photo} stroke={PALETTE.cardEdge} strokeWidth="1.2" />
      <circle cx={cx} cy={y + h * 0.36} r={headR} />
      <ellipse cx={cx} cy={y + h * 0.78} rx={w * 0.32} ry={h * 0.18} />
    </g>
  );
}

function LineaDato({ x, y, w }: { x: number; y: number; w: number }) {
  return <rect x={x} y={y} width={w} height="6" rx="3" fill={PALETTE.inkLight} />;
}

function GuiaAnverso({ mostrarZonasLeidas }: { mostrarZonasLeidas: boolean }) {
  const px = 24;
  const py = 24;
  const fotoW = 72;
  const fotoH = 88;
  return (
    <IlustracionTarjeta>
      <SiluetaFoto x={px} y={py} w={fotoW} h={fotoH} />
      {mostrarZonasLeidas && <ZonaLeida x={px - 4} y={py - 4} width={fotoW + 8} height={fotoH + 8} label="1" />}
      <rect x={px + 8} y={py + fotoH - 18} width={22} height={14} rx="2" fill={PALETTE.chip} opacity="0.85" />
      <LineaDato x={112} y={32} w={140} />
      <LineaDato x={112} y={48} w={168} />
      <LineaDato x={112} y={64} w={120} />
      <LineaDato x={112} y={80} w={96} />
      {mostrarZonasLeidas && <ZonaLeida x={106} y={26} width={186} height={68} label="2" />}
    </IlustracionTarjeta>
  );
}

function GuiaReverso({ mostrarZonasLeidas }: { mostrarZonasLeidas: boolean }) {
  const mrzY = CARD_H - 52;
  return (
    <IlustracionTarjeta>
      <LineaDato x={24} y={28} w={200} />
      <LineaDato x={24} y={44} w={168} />
      <LineaDato x={24} y={60} w={184} />
      <rect x={24} y={78} width={96} height={36} rx="4" fill={PALETTE.inkLight} opacity="0.45" />
      {mostrarZonasLeidas && <ZonaLeida x={20} y={24} width={280} height={96} label="1" />}
      <rect x={20} y={mrzY} width={280} height="11" rx="2" fill={PALETTE.mrz} opacity="0.75" />
      <rect x={20} y={mrzY + 14} width={280} height="11" rx="2" fill={PALETTE.mrz} opacity="0.75" />
      <rect x={20} y={mrzY + 28} width={280} height="11" rx="2" fill={PALETTE.mrz} opacity="0.75" />
      {mostrarZonasLeidas && <ZonaLeida x={18} y={mrzY - 6} width={284} height={50} label="2" />}
    </IlustracionTarjeta>
  );
}

function GuiaPasaporte({ mostrarZonasLeidas }: { mostrarZonasLeidas: boolean }) {
  const fotoX = 32;
  const fotoY = 36;
  const fotoW = 68;
  const fotoH = 84;
  const mrzY = PASS_H - 48;
  const innerW = PASS_W - 44;

  return (
    <IlustracionPasaporte>
      <SiluetaFoto x={fotoX} y={fotoY} w={fotoW} h={fotoH} />
      {mostrarZonasLeidas && (
        <ZonaLeida x={fotoX - 4} y={fotoY - 4} width={fotoW + 8} height={fotoH + 8} label="1" />
      )}
      <LineaDato x={fotoX + fotoW + 14} y={42} w={innerW - fotoW - 20} />
      <LineaDato x={fotoX + fotoW + 14} y={58} w={innerW - fotoW - 32} />
      <LineaDato x={fotoX} y={fotoY + fotoH + 18} w={innerW} />
      <LineaDato x={fotoX} y={fotoY + fotoH + 34} w={innerW - 16} />
      <LineaDato x={fotoX} y={fotoY + fotoH + 50} w={innerW - 28} />
      {mostrarZonasLeidas && (
        <ZonaLeida x={28} y={fotoY + fotoH + 10} width={innerW} height={58} label="2" />
      )}
      <rect x={28} y={mrzY} width={innerW} height="10" rx="2" fill={PALETTE.mrz} opacity="0.75" />
      <rect x={28} y={mrzY + 14} width={innerW} height="10" rx="2" fill={PALETTE.mrz} opacity="0.75" />
      {mostrarZonasLeidas && (
        <ZonaLeida x={26} y={mrzY - 4} width={innerW + 4} height={32} label="3" />
      )}
    </IlustracionPasaporte>
  );
}
