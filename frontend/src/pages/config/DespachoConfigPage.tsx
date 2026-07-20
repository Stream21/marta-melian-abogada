import { ConfigBreadcrumb } from '@/components/config/ConfigBreadcrumb';
import { DespachoConfigForm } from '@/components/config/despacho/DespachoConfigForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageShell } from '@/components/layout/PageShell';

export function DespachoConfigPage() {
  return (
    <>
      <ConfigBreadcrumb section="despacho" variant="list" />
      <PageShell>
        <PageHeader
          title="Datos del despacho"
          subtitle="Identidad del bufete, configuración común de documentos y datos bancarios para cobros."
        />
        <DespachoConfigForm />
      </PageShell>
    </>
  );
}
