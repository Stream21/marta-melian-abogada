import { useMemo } from 'react';
import { ChevronDown, Search, X } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface SelectFilterConfig {
  id: string;
  label: string;
  value: string;
  options: { value: string; label: string }[];
  onChange: (value: string) => void;
  emptyLabel?: string;
}

export interface ConfigListToolbarProps {
  search: string;
  onSearchChange: (value: string) => void;
  searchPlaceholder?: string;
  incluirInactivos: boolean;
  onIncluirInactivosChange: (value: boolean) => void;
  selectFilters?: SelectFilterConfig[];
  onClear?: () => void;
}

interface ActiveFilterChip {
  id: string;
  label: string;
  onRemove: () => void;
}

export function ConfigListToolbar({
  search,
  onSearchChange,
  searchPlaceholder = 'Buscar…',
  incluirInactivos,
  onIncluirInactivosChange,
  selectFilters = [],
  onClear,
}: ConfigListToolbarProps) {
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

    for (const filter of selectFilters) {
      if (!filter.value) continue;
      const option = filter.options.find((opt) => opt.value === filter.value);
      chips.push({
        id: filter.id,
        label: `${filter.label}: ${option?.label ?? filter.value}`,
        onRemove: () => filter.onChange(''),
      });
    }

    return chips;
  }, [search, selectFilters, onSearchChange]);

  return (
    <div className="border-b bg-card">
      <div className="table-toolbar border-b-0">
        <div className="relative min-w-[200px] flex-1 max-w-sm">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <input
            type="search"
            value={search}
            onChange={(e) => onSearchChange(e.target.value)}
            placeholder={searchPlaceholder}
            className={cn('input-field pl-9', search ? 'pr-9' : 'pr-3')}
            aria-label="Buscar"
          />
          {search && (
            <button
              type="button"
              onClick={() => onSearchChange('')}
              className="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
              aria-label="Quitar búsqueda"
            >
              <X className="h-3.5 w-3.5" />
            </button>
          )}
        </div>

        {selectFilters.map((filter) => (
          <div key={filter.id} className="relative">
            <select
              value={filter.value}
              onChange={(e) => filter.onChange(e.target.value)}
              aria-label={filter.label}
              className={cn(
                'appearance-none cursor-pointer rounded-lg border py-2 pl-3 text-sm transition-all focus:outline-none focus:ring-1 focus:ring-ring',
                filter.value ? 'pr-14' : 'pr-8',
                filter.value
                  ? 'border-primary bg-primary/5 font-medium text-primary'
                  : 'border-border bg-muted/50 text-muted-foreground',
              )}
            >
              <option value="">{filter.emptyLabel ?? filter.label}</option>
              {filter.options.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
            {filter.value ? (
              <button
                type="button"
                onClick={() => filter.onChange('')}
                className="absolute right-7 top-1/2 -translate-y-1/2 rounded-md p-0.5 text-primary transition-colors hover:bg-primary/10"
                aria-label={`Quitar filtro ${filter.label}`}
              >
                <X className="h-3.5 w-3.5" />
              </button>
            ) : null}
            <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
          </div>
        ))}

        <div className="inline-flex rounded-lg border bg-muted/50 p-0.5" role="group" aria-label="Estado">
          <button
            type="button"
            onClick={() => onIncluirInactivosChange(false)}
            className={cn(
              'rounded-md px-3 py-1.5 text-sm transition-colors',
              !incluirInactivos
                ? 'bg-card font-medium text-foreground shadow-sm'
                : 'text-muted-foreground hover:text-foreground',
            )}
          >
            Activos
          </button>
          <button
            type="button"
            onClick={() => onIncluirInactivosChange(true)}
            className={cn(
              'rounded-md px-3 py-1.5 text-sm transition-colors',
              incluirInactivos
                ? 'bg-card font-medium text-foreground shadow-sm'
                : 'text-muted-foreground hover:text-foreground',
            )}
          >
            Todos
          </button>
        </div>
      </div>

      {activeChips.length > 0 && (
        <div className="flex flex-wrap items-center gap-2 px-5 py-2.5 bg-muted/30">
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
          {activeChips.length >= 2 && onClear && (
            <button
              type="button"
              onClick={onClear}
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
