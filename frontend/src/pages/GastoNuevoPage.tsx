import { GastoForm } from '@/components/gastos/GastoForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageShell } from '@/components/layout/PageShell';

export function GastoNuevoPage() {
  return (
    <PageShell>
      <PageHeader title="Nuevo gasto" subtitle="Registre un gasto del bufete." />
      <div className="mx-auto max-w-[720px]">
        <GastoForm mode="create" />
      </div>
    </PageShell>
  );
}
