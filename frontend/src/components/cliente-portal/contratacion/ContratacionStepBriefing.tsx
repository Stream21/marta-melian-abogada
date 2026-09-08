import { Button } from '@/components/ui/button';

interface ContratacionStepBriefingProps {
  title: string;
  description: string;
  ctaLabel: string;
  onContinue: () => void;
}

/**
 * Pantalla de explicación antes de un paso (documento, firma, pago…).
 * Va en el flujo, no en un Dialog: así no se pierde detrás del PDF ni del welcome.
 */
export function ContratacionStepBriefing({
  title,
  description,
  ctaLabel,
  onContinue,
}: ContratacionStepBriefingProps) {
  return (
    <div className="flex min-h-0 flex-1 flex-col">
      <div className="flex min-h-0 flex-1 flex-col justify-center gap-5 px-1 py-6">
        <div className="space-y-3 text-center">
          <p className="text-xs font-semibold uppercase tracking-wider text-primary">Antes de seguir</p>
          <h2 className="text-balance text-2xl font-semibold leading-snug text-foreground">{title}</h2>
          <p className="text-pretty text-base leading-relaxed text-muted-foreground">{description}</p>
        </div>
      </div>
      <div className="shrink-0 border-t border-border bg-card pt-4 pb-[max(0.5rem,env(safe-area-inset-bottom))]">
        <Button type="button" size="lg" className="min-h-[52px] w-full text-base" onClick={onContinue}>
          {ctaLabel}
        </Button>
      </div>
    </div>
  );
}
