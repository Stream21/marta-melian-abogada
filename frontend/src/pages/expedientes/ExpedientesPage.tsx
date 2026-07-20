import { Link } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import { Plus } from 'lucide-react';
import { api } from '@/api/client';
import { ExpedientesTable } from '@/components/expedientes/ExpedientesTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageShell } from '@/components/layout/PageShell';
import { Button } from '@/components/ui/button';

export function ExpedientesPage() {
  const { data = [], isLoading, isFetching, refetch } = useQuery({
    queryKey: ['expedientes'],
    queryFn: () => api.getExpedientes(),
  });

  return (
    <PageShell>
      <PageHeader
        title="Expedientes"
        subtitle="Listado de expedientes activos"
        actions={
          <Button asChild>
            <Link to="/expedientes/nuevo">
              <Plus className="mr-2 h-4 w-4" />
              Nuevo expediente
            </Link>
          </Button>
        }
      />

      <ExpedientesTable
        data={data}
        isLoading={isLoading}
        isFetching={isFetching}
        onRefresh={() => void refetch()}
      />
    </PageShell>
  );
}
