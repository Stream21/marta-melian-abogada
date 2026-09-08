import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

export interface BriefingPlanStep {
  title: string;
  description?: string;
  active?: boolean;
}

interface ContratacionBriefingDialogProps {
  open: boolean;
  title: string;
  description: string;
  planSteps?: BriefingPlanStep[];
  children?: ReactNode;
  ctaLabel?: string;
  onContinue: () => void;
}

export function ContratacionBriefingDialog({
  open,
  title,
  description,
  planSteps,
  children,
  ctaLabel = 'Continuar',
  onContinue,
}: ContratacionBriefingDialogProps) {
  return (
    <Dialog open={open}>
      <DialogContent
        className={cn(
          'flex w-full max-w-none flex-col gap-0 overflow-hidden rounded-none border-0 p-0',
          'fixed inset-x-0 bottom-0 top-0 z-50 translate-x-0 translate-y-0',
          'h-[var(--portal-vh,100dvh)] max-h-[var(--portal-vh,100dvh)]',
          'pb-[env(safe-area-inset-bottom,0px)]',
          '[&>button]:hidden',
          'sm:left-[50%] sm:top-[50%] sm:z-50 sm:h-auto sm:max-h-[92vh] sm:max-w-md sm:translate-x-[-50%] sm:translate-y-[-50%] sm:rounded-xl sm:border',
        )}
        onPointerDownOutside={(e) => e.preventDefault()}
        onEscapeKeyDown={(e) => e.preventDefault()}
      >
        <div className="flex min-h-0 flex-1 flex-col overflow-y-auto px-5 pb-4 pt-14 sm:pt-12">
          <DialogHeader className="space-y-2 text-center sm:text-center">
            <DialogTitle className="text-xl leading-snug">{title}</DialogTitle>
            <DialogDescription className="text-center text-base leading-relaxed">
              {description}
            </DialogDescription>
          </DialogHeader>

          {planSteps && planSteps.length > 0 && (
            <ol className="my-6 space-y-2">
              {planSteps.map((step, index) => (
                <li
                  key={`${step.title}-${index}`}
                  className={cn(
                    'flex gap-3 rounded-xl border px-3 py-2.5 text-left',
                    step.active
                      ? 'border-primary/40 bg-primary/5'
                      : 'border-border bg-card',
                  )}
                >
                  <span
                    className={cn(
                      'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                      step.active
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-muted text-muted-foreground',
                    )}
                  >
                    {index + 1}
                  </span>
                  <div className="min-w-0">
                    <p className="text-sm font-semibold text-foreground">{step.title}</p>
                    {step.description && (
                      <p className="mt-0.5 text-xs leading-snug text-muted-foreground">
                        {step.description}
                      </p>
                    )}
                  </div>
                </li>
              ))}
            </ol>
          )}

          {children ? <div className="my-4 flex flex-1 flex-col justify-center">{children}</div> : null}
        </div>

        <DialogFooter className="shrink-0 flex-col gap-2 border-t border-border bg-card px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom,0px))] sm:flex-col">
          <Button type="button" size="lg" className="w-full" onClick={onContinue}>
            {ctaLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
