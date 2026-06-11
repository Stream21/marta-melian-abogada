import { cn } from '@/lib/utils';

interface VariableChipProps {
  variableKey: string;
  className?: string;
}

export function VariableChip({ variableKey, className }: VariableChipProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-md bg-primary/10 px-1.5 py-0.5 text-[11px] font-semibold text-primary',
        className,
      )}
    >
      [[{variableKey}]]
    </span>
  );
}
