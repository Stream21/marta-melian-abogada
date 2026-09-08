import { cn } from '@/lib/utils';

interface ContratacionFocusChromeProps {
  index: number;
  total: number;
  title: string;
  className?: string;
}

export function ContratacionFocusChrome({
  index,
  total,
  title,
  className,
}: ContratacionFocusChromeProps) {
  const actual = Math.min(Math.max(index + 1, 1), Math.max(total, 1));
  const progress = total > 0 ? (actual / total) * 100 : 0;

  return (
    <div className={cn('mb-3 space-y-2', className)}>
      <div className="flex items-start justify-between gap-3">
        <h1 className="min-w-0 flex-1 text-lg font-semibold leading-tight text-foreground">
          {title}
        </h1>
        {total > 0 && (
          <p
            className="shrink-0 rounded-full bg-primary/10 px-2.5 py-1 text-sm font-semibold tabular-nums text-primary"
            aria-label={`Paso ${actual} de ${total}`}
          >
            {actual}/{total}
          </p>
        )}
      </div>

      {total > 1 && (
        <div
          className="h-1.5 overflow-hidden rounded-full bg-muted"
          role="progressbar"
          aria-valuenow={actual}
          aria-valuemin={1}
          aria-valuemax={total}
          aria-label={`Progreso ${actual} de ${total}`}
        >
          <div
            className="h-full rounded-full bg-primary transition-[width] duration-300 ease-out motion-reduce:transition-none"
            style={{ width: `${progress}%` }}
          />
        </div>
      )}
    </div>
  );
}
