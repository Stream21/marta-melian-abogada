import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface MultiSelectFilterProps {
  label: string;
  emptyLabel?: string;
  values: string[];
  options: { value: string; label: string }[];
  onChange: (values: string[]) => void;
}

interface DropdownCoords {
  top: number;
  left: number;
  minWidth: number;
}

/**
 * Multi-select en portal fixed para no quedar recortado por paneles con overflow-hidden.
 */
export function MultiSelectFilter({
  label,
  emptyLabel,
  values,
  options,
  onChange,
}: MultiSelectFilterProps) {
  const [open, setOpen] = useState(false);
  const [coords, setCoords] = useState<DropdownCoords | null>(null);
  const triggerRef = useRef<HTMLButtonElement>(null);
  const menuRef = useRef<HTMLDivElement>(null);

  useLayoutEffect(() => {
    if (!open || !triggerRef.current) {
      setCoords(null);
      return;
    }

    const update = () => {
      const rect = triggerRef.current!.getBoundingClientRect();
      const menuWidth = Math.max(220, rect.width);
      let left = rect.left;
      if (left + menuWidth > window.innerWidth - 8) {
        left = Math.max(8, window.innerWidth - menuWidth - 8);
      }
      setCoords({
        top: rect.bottom + 4,
        left,
        minWidth: menuWidth,
      });
    };

    update();
    window.addEventListener('resize', update);
    window.addEventListener('scroll', update, true);
    return () => {
      window.removeEventListener('resize', update);
      window.removeEventListener('scroll', update, true);
    };
  }, [open]);

  useEffect(() => {
    if (!open) return;
    const handler = (event: MouseEvent) => {
      const target = event.target as Node;
      if (triggerRef.current?.contains(target) || menuRef.current?.contains(target)) {
        return;
      }
      setOpen(false);
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  const toggle = (value: string) => {
    onChange(values.includes(value) ? values.filter((v) => v !== value) : [...values, value]);
  };

  const hasSelection = values.length > 0;
  const selectedLabels = values.map(
    (value) => options.find((o) => o.value === value)?.label ?? value,
  );
  const displayText =
    values.length === 0
      ? (emptyLabel ?? label)
      : values.length === 1
        ? `${label}: ${selectedLabels[0]}`
        : values.length <= 2
          ? `${label}: ${selectedLabels.join(', ')}`
          : `${label}: ${selectedLabels.length} seleccionados`;

  return (
    <>
      <button
        ref={triggerRef}
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        aria-label={label}
        aria-expanded={open}
        title={hasSelection ? `${label}: ${selectedLabels.join(', ')}` : (emptyLabel ?? label)}
        className={cn(
          'flex h-9 min-w-[140px] max-w-[220px] items-center gap-2 rounded-lg border px-3 text-sm transition-all focus:outline-none focus:ring-1 focus:ring-ring',
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

      {open &&
        coords &&
        createPortal(
          <div
            ref={menuRef}
            role="listbox"
            aria-label={label}
            className="z-[80] max-h-[min(70vh,360px)] overflow-y-auto rounded-lg border bg-card p-2 shadow-lg"
            style={{
              position: 'fixed',
              top: coords.top,
              left: coords.left,
              minWidth: coords.minWidth,
            }}
          >
            <p className="px-2 pb-1.5 pt-0.5 text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
              {label}
            </p>
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
          </div>,
          document.body,
        )}
    </>
  );
}
