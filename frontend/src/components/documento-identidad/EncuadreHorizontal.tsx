import { cn } from '@/lib/utils';

interface EncuadreHorizontalProps {
  /** Vista compacta sin etiquetas flotantes (móvil). */
  compacto?: boolean;
  className?: string;
}

/** Marco guía ilustrativo: documento en posición horizontal (landscape). */
export function EncuadreHorizontal({ compacto = false, className }: EncuadreHorizontalProps) {
  return (
    <div
      className={cn(
        'flex w-full items-center justify-center',
        compacto ? 'py-2' : 'py-4',
        className,
      )}
      aria-hidden
    >
      <div
        className={cn(
          'relative rounded-lg border-2 border-dashed border-primary/40 bg-primary/5',
          compacto ? 'h-16 w-[85%] max-w-xs' : 'h-20 w-[88%] max-w-sm',
        )}
      >
        {!compacto && (
          <span className="absolute -top-5 left-0 text-[10px] font-medium uppercase tracking-wide text-primary/70">
            Horizontal
          </span>
        )}
        <div className="absolute inset-2 rounded border border-primary/15" />
      </div>
    </div>
  );
}
