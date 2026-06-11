import { ConfigBreadcrumb } from '@/components/config/ConfigBreadcrumb';
import { DespachoConfigForm } from '@/components/config/despacho/DespachoConfigForm';

export function DespachoConfigPage() {
  return (
    <div className="bg-muted/30">
      <ConfigBreadcrumb section="despacho" variant="list" />

      <div className="p-6 md:p-8">
        <div className="mx-auto flex max-w-[960px] flex-col gap-6">
          <div>
            <h1 className="page-title">Datos del despacho</h1>
            <p className="page-subtitle">
              Identidad del bufete, configuración común de documentos y datos bancarios para cobros.
            </p>
          </div>

          <DespachoConfigForm />
        </div>
      </div>
    </div>
  );
}
