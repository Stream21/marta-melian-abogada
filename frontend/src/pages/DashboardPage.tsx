import { useQuery } from '@tanstack/react-query';
import { Loader2 } from 'lucide-react';
import { api } from '@/api/client';
import { AlertasOperativas } from '@/components/dashboard/AlertasOperativas';
import { ActividadReciente } from '@/components/dashboard/ActividadReciente';
import { FaseDistribution } from '@/components/dashboard/FaseDistribution';
import { StatsCards } from '@/components/dashboard/StatsCards';
import { VencimientosTable } from '@/components/dashboard/VencimientosTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageShell } from '@/components/layout/PageShell';

export function DashboardPage() {
  const { data, isLoading, error, isFetching } = useQuery({
    queryKey: ['dashboard-kpis'],
    queryFn: () => api.getDashboardKpis(),
    refetchInterval: 60_000,
  });

  return (
    <PageShell>
      <PageHeader
        title="Dashboard"
        subtitle="Visión financiera y operativa del despacho: cobros, gastos, beneficio y plazos a revisar."
      />

      {isLoading && !data ? (
        <div className="flex items-center justify-center py-20 text-muted-foreground">
          <Loader2 className="mr-2 h-5 w-5 animate-spin" />
          Cargando indicadores…
        </div>
      ) : error ? (
        <div className="panel p-6 text-center text-destructive text-sm">
          No se pudo cargar el dashboard. Inténtelo de nuevo.
        </div>
      ) : data ? (
        <div className={`space-y-8 transition-opacity ${isFetching ? 'opacity-70' : ''}`}>
          <StatsCards
            financiero={data.financiero}
            operativo={data.operativo}
            periodoLabel={data.periodo.label}
          />

          <AlertasOperativas operativo={data.operativo} />

          <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 items-stretch">
            <div className="xl:col-span-2 flex flex-col min-h-[28rem]">
              <VencimientosTable items={data.vencimientosProximos} />
            </div>
            <div className="flex flex-col gap-6">
              <FaseDistribution
                porFase={data.porFase}
                totalActivos={data.operativo.expedientesActivos}
              />
              <div className="flex-1 min-h-[18rem]">
                <ActividadReciente
                  items={data.actividadReciente}
                  totalSinLeer={data.operativo.notificacionesSinLeer}
                />
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </PageShell>
  );
}
