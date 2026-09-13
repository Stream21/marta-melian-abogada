import {
  useEffect,
  useLayoutEffect,
  useMemo,
  useRef,
  useState,
  type KeyboardEvent,
  type ReactNode,
} from 'react';
import { createPortal } from 'react-dom';
import { SlidersHorizontal, X } from 'lucide-react';
import { MultiSelectFilter } from '@/components/config/MultiSelectFilter';
import { cn } from '@/lib/utils';

export interface SelectFilterConfig {
  id: string;
  label: string;
  values: string[];
  options: { value: string; label: string }[];
  onChange: (values: string[]) => void;
  emptyLabel?: string;
}

export interface ConfigListToolbarProps {
  search: string;
  onSearchChange: (value: string) => void;
  onSearchKeyDown?: (event: KeyboardEvent<HTMLInputElement>) => void;
  searchPlaceholder?: string;
  incluirInactivos?: boolean;
  onIncluirInactivosChange?: (value: boolean) => void;
  /** Filtros siempre visibles en la barra. */
  selectFilters?: SelectFilterConfig[];
  /** Filtros secundarios dentro de «Más filtros» (evita saturar la barra). */
  moreFilters?: SelectFilterConfig[];
  trailing?: ReactNode;
}

interface ActiveFilterChip {
  id: string;
  label: string;
  onRemove: () => void;
}

interface DropdownCoords {
  top: number;
  right: number;
  width: number;
}

export function ConfigListToolbar({
  search,
  onSearchChange,
  onSearchKeyDown,
  searchPlaceholder = 'Buscar…',
  incluirInactivos,
  onIncluirInactivosChange,
  selectFilters = [],
  moreFilters = [],
  trailing,
}: ConfigListToolbarProps) {
  const [moreOpen, setMoreOpen] = useState(false);
  const [moreCoords, setMoreCoords] = useState<DropdownCoords | null>(null);
  const moreTriggerRef = useRef<HTMLButtonElement>(null);
  const moreMenuRef = useRef<HTMLDivElement>(null);

  const allFilters = useMemo(
    () => [...selectFilters, ...moreFilters],
    [selectFilters, moreFilters],
  );

  const activeChips = useMemo<ActiveFilterChip[]>(() => {
    const chips: ActiveFilterChip[] = [];
    const trimmed = search.trim();

    if (trimmed) {
      const preview = trimmed.length > 28 ? `${trimmed.slice(0, 28)}…` : trimmed;
      chips.push({
        id: 'search',
        label: `Búsqueda: «${preview}»`,
        onRemove: () => onSearchChange(''),
      });
    }

    for (const filter of allFilters) {
      for (const value of filter.values) {
        const option = filter.options.find((opt) => opt.value === value);
        chips.push({
          id: `${filter.id}-${value}`,
          label: `${filter.label}: ${option?.label ?? value}`,
          onRemove: () => filter.onChange(filter.values.filter((v) => v !== value)),
        });
      }
    }

    return chips;
  }, [search, allFilters, onSearchChange]);

  const moreActiveCount = useMemo(
    () => moreFilters.reduce((acc, f) => acc + f.values.length, 0),
    [moreFilters],
  );

  useLayoutEffect(() => {
    if (!moreOpen || !moreTriggerRef.current) {
      setMoreCoords(null);
      return;
    }

    const update = () => {
      const rect = moreTriggerRef.current!.getBoundingClientRect();
      const width = Math.min(window.innerWidth - 16, 320);
      setMoreCoords({
        top: rect.bottom + 4,
        right: Math.max(8, window.innerWidth - rect.right),
        width,
      });
    };

    update();
    window.addEventListener('resize', update);
    window.addEventListener('scroll', update, true);
    return () => {
      window.removeEventListener('resize', update);
      window.removeEventListener('scroll', update, true);
    };
  }, [moreOpen]);

  useEffect(() => {
    if (!moreOpen) return;
    const handler = (event: MouseEvent) => {
      const target = event.target as Node;
      if (moreTriggerRef.current?.contains(target) || moreMenuRef.current?.contains(target)) {
        return;
      }
      setMoreOpen(false);
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [moreOpen]);

  const clearAll = () => {
    onSearchChange('');
    for (const filter of allFilters) {
      filter.onChange([]);
    }
    if (incluirInactivos !== undefined && onIncluirInactivosChange) {
      onIncluirInactivosChange(false);
    }
  };

  return (
    <div className="border-b bg-card">
      <div className="table-toolbar border-b-0">
        <input
          type="search"
          value={search}
          onChange={(e) => onSearchChange(e.target.value)}
          onKeyDown={onSearchKeyDown}
          placeholder={searchPlaceholder}
          className="input-field h-9 max-w-sm min-w-[200px] flex-1"
          aria-label="Buscar"
        />

        {selectFilters.map((filter) => (
          <MultiSelectFilter
            key={filter.id}
            label={filter.label}
            emptyLabel={filter.emptyLabel}
            values={filter.values}
            options={filter.options}
            onChange={filter.onChange}
          />
        ))}

        {moreFilters.length > 0 && (
          <>
            <button
              ref={moreTriggerRef}
              type="button"
              onClick={() => setMoreOpen((prev) => !prev)}
              aria-expanded={moreOpen}
              aria-label="Más filtros"
              className={cn(
                'flex h-9 items-center gap-2 rounded-lg border px-3 text-sm transition-all focus:outline-none focus:ring-1 focus:ring-ring',
                moreActiveCount > 0
                  ? 'border-primary bg-primary/5 font-medium text-primary'
                  : 'border-border bg-muted/50 text-muted-foreground',
              )}
            >
              <SlidersHorizontal className="h-3.5 w-3.5 shrink-0" />
              Más filtros
              {moreActiveCount > 0 && (
                <span className="flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-primary-foreground">
                  {moreActiveCount}
                </span>
              )}
            </button>

            {moreOpen &&
              moreCoords &&
              createPortal(
                <div
                  ref={moreMenuRef}
                  className="z-[80] max-h-[min(70vh,480px)] overflow-y-auto rounded-lg border bg-card p-3 shadow-lg"
                  style={{
                    position: 'fixed',
                    top: moreCoords.top,
                    right: moreCoords.right,
                    width: moreCoords.width,
                  }}
                >
                  <p className="px-1 pb-2 text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                    Filtros adicionales
                  </p>
                  <div className="flex flex-col gap-3">
                    {moreFilters.map((filter) => (
                      <div key={filter.id} className="space-y-1.5">
                        <p className="px-1 text-xs font-medium text-foreground">{filter.label}</p>
                        <div className="flex flex-col gap-0.5">
                          {filter.options.map((opt) => {
                            const checked = filter.values.includes(opt.value);
                            return (
                              <label
                                key={opt.value}
                                className="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-sm hover:bg-muted/50"
                              >
                                <input
                                  type="checkbox"
                                  checked={checked}
                                  onChange={() =>
                                    filter.onChange(
                                      checked
                                        ? filter.values.filter((v) => v !== opt.value)
                                        : [...filter.values, opt.value],
                                    )
                                  }
                                  className="h-4 w-4 shrink-0 rounded border-border text-primary focus:ring-ring"
                                />
                                <span className="leading-none">{opt.label}</span>
                              </label>
                            );
                          })}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>,
                document.body,
              )}
          </>
        )}

        {incluirInactivos !== undefined && onIncluirInactivosChange && (
          <div
            className="inline-flex rounded-lg border bg-muted/50 p-0.5"
            role="group"
            aria-label="Estado"
          >
            <button
              type="button"
              onClick={() => onIncluirInactivosChange(false)}
              className={
                !incluirInactivos
                  ? 'rounded-md bg-card px-3 py-1.5 text-sm font-medium text-foreground shadow-sm transition-colors'
                  : 'rounded-md px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground'
              }
            >
              Activos
            </button>
            <button
              type="button"
              onClick={() => onIncluirInactivosChange(true)}
              className={
                incluirInactivos
                  ? 'rounded-md bg-card px-3 py-1.5 text-sm font-medium text-foreground shadow-sm transition-colors'
                  : 'rounded-md px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground'
              }
            >
              Todos
            </button>
          </div>
        )}

        {trailing}
      </div>

      {activeChips.length > 0 && (
        <div className="flex flex-wrap items-center gap-2 border-t bg-muted/30 px-5 py-2.5">
          <span className="text-xs font-medium text-muted-foreground">Filtros activos:</span>
          {activeChips.map((chip) => (
            <span
              key={chip.id}
              className="inline-flex max-w-xs items-center gap-1 rounded-full border border-primary/20 bg-primary/5 py-1 pl-2.5 pr-1 text-xs font-medium text-primary"
            >
              <span className="truncate">{chip.label}</span>
              <button
                type="button"
                onClick={chip.onRemove}
                className="shrink-0 rounded-full p-0.5 transition-colors hover:bg-primary/10"
                aria-label={`Quitar ${chip.label}`}
              >
                <X className="h-3 w-3" />
              </button>
            </span>
          ))}
          {activeChips.length >= 1 && (
            <button
              type="button"
              onClick={clearAll}
              className="text-xs text-muted-foreground underline-offset-2 transition-colors hover:text-foreground hover:underline"
            >
              Limpiar todo
            </button>
          )}
        </div>
      )}
    </div>
  );
}
