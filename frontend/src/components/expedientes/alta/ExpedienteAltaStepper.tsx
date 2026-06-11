import { Check, ClipboardList, CreditCard, FileText, User } from 'lucide-react';
import { cn } from '@/lib/utils';

const STEPS = [
  { id: 1, label: 'Datos Cliente', icon: User },
  { id: 2, label: 'Selección Trámite', icon: FileText },
  { id: 3, label: 'Pago', icon: CreditCard },
  { id: 4, label: 'Resumen', icon: ClipboardList },
  { id: 5, label: 'Finalizar', icon: Check },
] as const;

interface ExpedienteAltaStepperProps {
  currentStep: number;
}

export function ExpedienteAltaStepper({ currentStep }: ExpedienteAltaStepperProps) {
  return (
    <div className="mb-8 flex items-center justify-between gap-2">
      {STEPS.map((step, index) => {
        const Icon = step.icon;
        const isCompleted = currentStep > step.id;
        const isActive = currentStep === step.id;

        return (
          <div key={step.id} className="flex flex-1 items-center">
            <div className="flex flex-col items-center gap-1.5 min-w-0">
              <div
                className={cn(
                  'flex h-10 w-10 items-center justify-center rounded-full border-2 transition-colors',
                  isCompleted && 'border-primary bg-primary text-primary-foreground',
                  isActive && 'border-primary bg-primary/10 text-primary',
                  !isCompleted && !isActive && 'border-border bg-muted text-muted-foreground',
                )}
              >
                {isCompleted ? <Check className="h-4 w-4" /> : <Icon className="h-4 w-4" />}
              </div>
              <span
                className={cn(
                  'text-center text-[10px] font-bold uppercase tracking-wide hidden sm:block',
                  isActive ? 'text-primary' : 'text-muted-foreground',
                )}
              >
                {step.label}
              </span>
            </div>
            {index < STEPS.length - 1 && (
              <div
                className={cn(
                  'mx-2 h-0.5 flex-1',
                  currentStep > step.id ? 'bg-primary' : 'bg-border',
                )}
              />
            )}
          </div>
        );
      })}
    </div>
  );
}
