import { useQuery } from '@tanstack/react-query';
import { Loader2 } from 'lucide-react';
import { api } from '@/api/client';
import { GastoForm } from '@/components/gastos/GastoForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageShell } from '@/components/layout/PageShell';

interface GastoDetailPageProps {
  gastoId: string;
}

export function GastoDetailPage({ gastoId }: GastoDetailPageProps) {
  const { data, isLoading, error } = useQuery({
    queryKey: ['gasto', gastoId],
    queryFn: () => api.getGasto(gastoId),
  });

  return (
    <PageShell>
      <PageHeader
        title={data?.concepto ?? 'Editar gasto'}
        subtitle="Modifique los datos o adjunte la factura."
      />

      <div className="mx-auto max-w-[720px]">
        {isLoading ? (
          <div className="flex items-center justify-center py-20 text-muted-foreground">
            <Loader2 className="mr-2 h-5 w-5 animate-spin" />
            Cargando gasto…
          </div>
        ) : error || !data ? (
          <div className="panel p-6 text-center text-destructive text-sm">
            No se pudo cargar el gasto.
          </div>
        ) : (
          <GastoForm mode="edit" gastoId={gastoId} initial={data} />
        )}
      </div>
    </PageShell>
  );
}
