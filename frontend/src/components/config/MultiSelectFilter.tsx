import { useEffect, useRef, useState } from 'react';
import { ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface MultiSelectFilterProps {
  label: string;
  emptyLabel?: string;
  values: string[];
  options: { value: string; label: string }[];
  onChange: (values: string[]) => void;
}

export function MultiSelectFilter({
  label,
  emptyLabel,
  values,
  options,
  onChange,
}: MultiSelectFilterProps) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;
    const handler = (event: MouseEvent) => {
      if (ref.current && !ref.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  const toggle = (value: string) => {
    onChange(values.includes(value) ? values.filter((v) => v !== value) : [...values, value]);
  };

  const hasSelection = values.length > 0;
  const displayText =
    values.length === 0
      ? (emptyLabel ?? label)
      : values.length === 1
        ? (options.find((o) => o.value === values[0])?.label ?? values[0])
        : `${label} (${values.length})`;

  return (
    <div ref={ref} className="relative">
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        aria-label={label}
        aria-expanded={open}
        className={cn(
          'flex h-9 min-w-[140px] items-center gap-2 rounded-lg border px-3 text-sm transition-all focus:outline-none focus:ring-1 focus:ring-ring',
          hasSelection
            ? 'border-primary bg-primary/5 font-medium text-primary'
            : 'border-border bg-muted/50 text-muted-foreground',
        )}
      >
        <span className="min-w-0 flex-1 truncate text-left leading-none">{displayText}</span>
        <ChevronDown
          className={cn(
            'h-3.5 w-3.5 shrink-0 text-muted-foreground transition-transform',
            open && 'rotate-180',
          )}
        />
      </button>

      {open && (
        <div className="absolute left-0 top-full z-30 mt-1 min-w-[220px] rounded-lg border bg-card p-2 shadow-lg">
          {options.map((opt) => (
            <label
              key={opt.value}
              className="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-sm hover:bg-muted/50"
            >
              <input
                type="checkbox"
                checked={values.includes(opt.value)}
                onChange={() => toggle(opt.value)}
                className="h-4 w-4 shrink-0 rounded border-border text-primary focus:ring-ring"
              />
              <span className="leading-none">{opt.label}</span>
            </label>
          ))}
        </div>
      )}
    </div>
  );
}
