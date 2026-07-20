import { useMemo, useState } from 'react';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { AlertTriangle, Loader2 } from 'lucide-react';
import { api, type CobrosGlobalesFilters } from '@/api/client';
import { CobrosGlobalesKpis } from '@/components/facturacion/CobrosGlobalesKpis';
import { CobrosGlobalesTable } from '@/components/facturacion/CobrosGlobalesTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageShell } from '@/components/layout/PageShell';
import { useDeferredSearch } from '@/hooks/useDeferredSearch';
import { cn } from '@/lib/utils';

export function FacturacionGlobalPage() {
  const [estadoCobro, setEstadoCobro] = useState<string[]>([]);
  const [holdedEstado, setHoldedEstado] = useState<string[]>([]);
  const [tipo, setTipo] = useState<string[]>([]);
  const { input: searchInput, setInput: setSearchInput, query: searchQuery, onSearchKeyDown } =
    useDeferredSearch();

  const filters: CobrosGlobalesFilters = useMemo(() => {
    const f: CobrosGlobalesFilters = {};
    if (estadoCobro.length) f.estadoCobro = estadoCobro;
    if (holdedEstado.length) f.holdedEstado = holdedEstado;
    if (tipo.length) f.tipo = tipo;
    if (searchQuery.trim()) f.q = searchQuery.trim();
    return f;
  }, [estadoCobro, holdedEstado, tipo, searchQuery]);

  const { data, isLoading, isFetching, error } = useQuery({
    queryKey: ['cobros-globales', filters],
    queryFn: () => api.getCobrosGlobales(filters),
    placeholderData: keepPreviousData,
  });

  const pendientesSync = data?.kpis.pendienteSyncHolded ?? 0;
  const isInitialLoad = isLoading && !data;

  return (
    <PageShell>
      <PageHeader
        title="Facturación"
        subtitle="Vista global de cobros: estado del pago y sincronización con Holded."
      />

      <div className="space-y-6">
        {pendientesSync > 0 && (
          <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <AlertTriangle className="h-4 w-4 shrink-0 mt-0.5" />
            <p>
              Hay <strong>{pendientesSync}</strong> cobro{pendientesSync === 1 ? '' : 's'} pendiente
              {pendientesSync === 1 ? '' : 's'} de sincronizar con Holded. Puede reintentarlo desde la
              tabla.
            </p>
          </div>
        )}

        {isInitialLoad ? (
          <div className="flex items-center justify-center py-20 text-muted-foreground">
            <Loader2 className="mr-2 h-5 w-5 animate-spin" />
            Cargando cobros…
          </div>
        ) : error ? (
          <div className="panel p-6 text-center text-destructive text-sm">
            No se pudo cargar la facturación global.
          </div>
        ) : data ? (
          <div className={cn('space-y-6 transition-opacity', isFetching && 'opacity-60')}>
            <CobrosGlobalesKpis kpis={data.kpis} />

            <CobrosGlobalesTable
              items={data.items}
              search={searchInput}
              onSearchChange={setSearchInput}
              onSearchKeyDown={onSearchKeyDown}
              searchPlaceholder="Buscar expediente o cliente… (Enter)"
              estadoCobro={estadoCobro}
              onEstadoCobroChange={setEstadoCobro}
              holdedEstado={holdedEstado}
              onHoldedEstadoChange={setHoldedEstado}
              tipo={tipo}
              onTipoChange={setTipo}
            />
          </div>
        ) : null}
      </div>
    </PageShell>
  );
}
