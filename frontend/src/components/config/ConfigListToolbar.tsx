import { useMemo, type KeyboardEvent, type ReactNode } from 'react';
import { X } from 'lucide-react';
import { MultiSelectFilter } from '@/components/config/MultiSelectFilter';

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
  selectFilters?: SelectFilterConfig[];
  trailing?: ReactNode;
}

interface ActiveFilterChip {
  id: string;
  label: string;
  onRemove: () => void;
}

export function ConfigListToolbar({
  search,
  onSearchChange,
  onSearchKeyDown,
  searchPlaceholder = 'Buscar…',
  incluirInactivos,
  onIncluirInactivosChange,
  selectFilters = [],
  trailing,
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
  }, [search, selectFilters, onSearchChange]);

  const clearAll = () => {
    onSearchChange('');
    for (const filter of selectFilters) {
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
