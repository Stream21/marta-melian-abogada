import { useMemo, useState } from 'react';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Link } from '@tanstack/react-router';
import { Loader2, Plus } from 'lucide-react';
import { api, type GastosFilters } from '@/api/client';
import { GastosKpis } from '@/components/gastos/GastosKpis';
import { GastosTable } from '@/components/gastos/GastosTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageShell } from '@/components/layout/PageShell';
import { Button } from '@/components/ui/button';
import { useDeferredSearch } from '@/hooks/useDeferredSearch';
import { cn } from '@/lib/utils';

export function GastosPage() {
  const [fechaDesde, setFechaDesde] = useState('');
  const [fechaHasta, setFechaHasta] = useState('');
  const [categoria, setCategoria] = useState('');
  const { input: searchInput, setInput: setSearchInput, query: searchQuery, onSearchKeyDown } =
    useDeferredSearch();

  const filters: GastosFilters = useMemo(() => {
    const f: GastosFilters = {};
    if (fechaDesde) f.fechaDesde = fechaDesde;
    if (fechaHasta) f.fechaHasta = fechaHasta;
    if (categoria.trim()) f.categoria = categoria.trim();
    if (searchQuery.trim()) f.q = searchQuery.trim();
    return f;
  }, [fechaDesde, fechaHasta, categoria, searchQuery]);

  const { data, isLoading, isFetching, error } = useQuery({
    queryKey: ['gastos', filters],
    queryFn: () => api.getGastos(filters),
    placeholderData: keepPreviousData,
  });

  const isInitialLoad = isLoading && !data;

  return (
    <PageShell>
      <PageHeader
        title="Gastos"
        subtitle="Registro de gastos del bufete, con totales y factura opcional."
        actions={
          <Button asChild className="gap-1.5">
            <Link to="/gastos/nuevo">
              <Plus className="h-4 w-4" />
              Nuevo gasto
            </Link>
          </Button>
        }
      />

      <div className="space-y-6">
        {isInitialLoad ? (
          <div className="flex items-center justify-center py-20 text-muted-foreground">
            <Loader2 className="mr-2 h-5 w-5 animate-spin" />
            Cargando gastos…
          </div>
        ) : error ? (
          <div className="panel p-6 text-center text-destructive text-sm">
            No se pudo cargar el listado de gastos.
          </div>
        ) : data ? (
          <div className={cn('space-y-6 transition-opacity', isFetching && 'opacity-60')}>
            <GastosKpis kpis={data.kpis} />
            <GastosTable
              items={data.items}
              search={searchInput}
              onSearchChange={setSearchInput}
              onSearchKeyDown={onSearchKeyDown}
              fechaDesde={fechaDesde}
              onFechaDesdeChange={setFechaDesde}
              fechaHasta={fechaHasta}
              onFechaHastaChange={setFechaHasta}
              categoria={categoria}
              onCategoriaChange={setCategoria}
            />
          </div>
        ) : null}
      </div>
    </PageShell>
  );
}
