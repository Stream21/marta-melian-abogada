import { useEffect, useId, useMemo, useRef, useState, type KeyboardEvent } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import { FolderOpen, Loader2, Search, Users, X } from 'lucide-react';
import { api, type SearchHit } from '@/api/client';
import { capitalizeDisplay } from '@/lib/capitalize-display';
import { cn } from '@/lib/utils';

const TYPE_META: Record<string, { label: string; icon: typeof Search }> = {
  cliente: { label: 'Clientes', icon: Users },
  expediente: { label: 'Expedientes', icon: FolderOpen },
};

function formatHitTitle(hit: SearchHit): string {
  if (hit.type === 'expediente') {
    const sep = ' — ';
    const idx = hit.title.indexOf(sep);
    if (idx >= 0) {
      return `${hit.title.slice(0, idx)}${sep}${capitalizeDisplay(hit.title.slice(idx + sep.length))}`;
    }
    return hit.title;
  }
  return capitalizeDisplay(hit.title);
}

function formatHitSubtitle(hit: SearchHit): string {
  if (!hit.subtitle) return '';
  if (hit.type === 'expediente') {
    const parts = hit.subtitle.split(' · ');
    return parts
      .map((part, i) => (i === 0 ? capitalizeDisplay(part) : part))
      .join(' · ');
  }
  return capitalizeDisplay(hit.subtitle);
}

function useDebouncedValue<T>(value: T, delayMs: number): T {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const t = window.setTimeout(() => setDebounced(value), delayMs);
    return () => window.clearTimeout(t);
  }, [value, delayMs]);
  return debounced;
}

function groupHits(hits: SearchHit[]): Array<{ type: string; items: SearchHit[] }> {
  const order = ['expediente', 'cliente'];
  const map = new Map<string, SearchHit[]>();
  for (const hit of hits) {
    const list = map.get(hit.type) ?? [];
    list.push(hit);
    map.set(hit.type, list);
  }
  const groups: Array<{ type: string; items: SearchHit[] }> = [];
  for (const type of order) {
    const items = map.get(type);
    if (items?.length) {
      groups.push({ type, items });
      map.delete(type);
    }
  }
  for (const [type, items] of map) {
    groups.push({ type, items });
  }
  return groups;
}

export interface GlobalSearchBarProps {
  /** Fuerza foco (p. ej. atajo Ctrl/⌘K). */
  focusRequestId?: number;
  shortcutLabel?: string;
  className?: string;
}

/**
 * Buscador global inline: resultados en panel desplegable bajo el input (sin modal).
 */
export function GlobalSearchBar({
  focusRequestId = 0,
  shortcutLabel,
  className,
}: GlobalSearchBarProps) {
  const navigate = useNavigate();
  const rootRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);
  const listId = useId();
  const [query, setQuery] = useState('');
  const [open, setOpen] = useState(false);
  const [activeIndex, setActiveIndex] = useState(0);
  const debouncedQuery = useDebouncedValue(query.trim(), 250);

  const { data, isFetching, isError } = useQuery({
    queryKey: ['search-global', debouncedQuery],
    queryFn: () => api.searchGlobal({ q: debouncedQuery, limit: 20 }),
    enabled: open && debouncedQuery.length >= 2,
  });

  const hits = data?.hits ?? [];
  const groups = useMemo(() => groupHits(hits), [hits]);
  const flatHits = useMemo(() => groups.flatMap((g) => g.items), [groups]);
  const showPanel = open && (query.trim().length > 0 || debouncedQuery.length >= 2);

  useEffect(() => {
    if (focusRequestId > 0) {
      inputRef.current?.focus();
      setOpen(true);
    }
  }, [focusRequestId]);

  useEffect(() => {
    setActiveIndex(0);
  }, [debouncedQuery, hits.length]);

  useEffect(() => {
    const onPointerDown = (event: MouseEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', onPointerDown);
    return () => document.removeEventListener('mousedown', onPointerDown);
  }, []);

  const selectHit = (hit: SearchHit) => {
    setQuery('');
    setOpen(false);
    inputRef.current?.blur();
    if (hit.type === 'cliente') {
      void navigate({
        to: '/clientes/$clienteId',
        params: { clienteId: hit.id },
      } as never);
      return;
    }
    if (hit.type === 'expediente') {
      void navigate({
        to: '/expedientes/$expedienteId',
        params: { expedienteId: hit.id },
      } as never);
      return;
    }
    void navigate({ to: hit.href as never });
  };

  const clearQuery = () => {
    setQuery('');
    setActiveIndex(0);
    inputRef.current?.focus();
  };

  const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      if (query) {
        clearQuery();
      } else {
        setOpen(false);
        inputRef.current?.blur();
      }
      return;
    }
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      setOpen(true);
      if (flatHits.length === 0) return;
      setActiveIndex((i) => (i + 1) % flatHits.length);
      return;
    }
    if (event.key === 'ArrowUp') {
      event.preventDefault();
      if (flatHits.length === 0) return;
      setActiveIndex((i) => (i - 1 + flatHits.length) % flatHits.length);
      return;
    }
    if (event.key === 'Enter') {
      const hit = flatHits[activeIndex];
      if (hit) {
        event.preventDefault();
        selectHit(hit);
      }
    }
  };

  let flatOffset = 0;

  return (
    <div ref={rootRef} className={cn('relative min-w-0 flex-1', className)}>
      <div
        className={cn(
          'flex h-10 w-full items-center gap-2 rounded-lg border bg-muted/40 px-3 transition-colors',
          open
            ? 'border-primary/40 bg-card ring-2 ring-primary/20'
            : 'border-border hover:bg-muted',
        )}
      >
        <Search className="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden />
        <input
          ref={inputRef}
          value={query}
          onChange={(e) => {
            setQuery(e.target.value);
            setOpen(true);
          }}
          onFocus={() => setOpen(true)}
          onKeyDown={onKeyDown}
          placeholder="Buscar clientes o expedientes…"
          className="min-w-0 flex-1 bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground"
          role="combobox"
          aria-expanded={showPanel}
          aria-controls={listId}
          aria-autocomplete="list"
          autoComplete="off"
        />
        {isFetching && (
          <Loader2 className="h-4 w-4 shrink-0 animate-spin text-muted-foreground" aria-hidden />
        )}
        {query ? (
          <button
            type="button"
            onClick={clearQuery}
            className="rounded p-0.5 text-muted-foreground hover:bg-muted hover:text-foreground"
            aria-label="Limpiar búsqueda"
          >
            <X className="h-3.5 w-3.5" />
          </button>
        ) : shortcutLabel ? (
          <kbd className="hidden shrink-0 rounded border border-border bg-card px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground sm:inline">
            {shortcutLabel}
          </kbd>
        ) : null}
      </div>

      {showPanel && (
        <div
          id={listId}
          role="listbox"
          className="absolute left-0 right-0 top-[calc(100%+6px)] z-50 max-h-[min(60vh,420px)] overflow-y-auto overscroll-contain rounded-xl border border-border bg-card p-2 shadow-lg"
        >
          {debouncedQuery.length < 2 && (
            <p className="px-3 py-4 text-center text-sm text-muted-foreground">
              Escriba al menos 2 caracteres.
            </p>
          )}

          {debouncedQuery.length >= 2 && isError && (
            <p className="px-3 py-4 text-center text-sm text-destructive" role="alert">
              No se pudo completar la búsqueda.
            </p>
          )}

          {debouncedQuery.length >= 2 && !isError && !isFetching && flatHits.length === 0 && (
            <p className="px-3 py-4 text-center text-sm text-muted-foreground">
              Sin resultados para «{debouncedQuery}».
            </p>
          )}

          {groups.map((group) => {
            const meta = TYPE_META[group.type] ?? { label: group.type, icon: Search };
            const Icon = meta.icon;
            const start = flatOffset;
            flatOffset += group.items.length;

            return (
              <div key={group.type} className="mb-1 last:mb-0">
                <p className="section-label px-2 py-1.5">{meta.label}</p>
                <ul className="space-y-0.5">
                  {group.items.map((hit, i) => {
                    const index = start + i;
                    const active = index === activeIndex;
                    return (
                      <li key={`${hit.type}-${hit.id}`}>
                        <button
                          type="button"
                          role="option"
                          aria-selected={active}
                          className={cn(
                            'flex w-full items-start gap-3 rounded-lg px-2.5 py-2 text-left transition-colors',
                            active ? 'bg-primary/10 text-foreground' : 'hover:bg-muted/80',
                          )}
                          onMouseEnter={() => setActiveIndex(index)}
                          onClick={() => selectHit(hit)}
                        >
                          <Icon
                            className={cn(
                              'mt-0.5 h-4 w-4 shrink-0',
                              active ? 'text-primary' : 'text-muted-foreground',
                            )}
                            aria-hidden
                          />
                          <span className="min-w-0 flex-1">
                            <span className="block truncate text-sm font-medium">
                              {formatHitTitle(hit)}
                            </span>
                            {hit.subtitle ? (
                              <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                                {formatHitSubtitle(hit)}
                              </span>
                            ) : null}
                          </span>
                        </button>
                      </li>
                    );
                  })}
                </ul>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
