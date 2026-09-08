import { ArrowLeft } from 'lucide-react';
import { cn } from '@/lib/utils';

interface PortalCapturaSubheaderProps {
  onVolver?: () => void;
  volverLabel?: string;
  pasoActual?: number;
  pasosTotal?: number;
  /** Texto breve junto al indicador (p. ej. «Delantera»). */
  leyenda?: string;
  className?: string;
}

export function PortalCapturaSubheader({
  onVolver,
  volverLabel = 'Volver',
  pasoActual,
  pasosTotal,
  leyenda,
  className,
}: PortalCapturaSubheaderProps) {
  const muestraPasos =
    pasosTotal != null && pasosTotal >= 1 && pasoActual != null && pasoActual >= 1;

  return (
    <div
      className={cn(
        'flex shrink-0 items-center justify-between gap-3 border-b border-border pb-1.5',
        className,
      )}
    >
      {onVolver ? (
        <button
          type="button"
          onClick={onVolver}
          className="inline-flex min-h-[40px] items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
        >
          <ArrowLeft className="h-4 w-4" />
          {volverLabel}
        </button>
      ) : (
        <span aria-hidden />
      )}

      {muestraPasos ? (
        <div
          className="flex items-center gap-2"
          aria-label={`Paso ${pasoActual} de ${pasosTotal}${leyenda ? `: ${leyenda}` : ''}`}
        >
          {pasosTotal! > 1 &&
            Array.from({ length: pasosTotal! }, (_, i) => i + 1).map((n) => (
              <span
                key={n}
                className={cn(
                  'h-2 w-6 rounded-full transition-colors',
                  n <= pasoActual! ? 'bg-primary' : 'bg-border',
                )}
              />
            ))}
          <span className="text-xs font-medium text-muted-foreground">
            {leyenda ? `${leyenda} · ` : ''}
            {pasoActual} de {pasosTotal}
          </span>
        </div>
      ) : (
        <span aria-hidden />
      )}
    </div>
  );
}
