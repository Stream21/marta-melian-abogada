import { ConfigBreadcrumb } from '@/components/config/ConfigBreadcrumb';
import { ServicioForm } from '@/components/config/ServicioForm';

export function ServiciosNuevoPage() {
  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <ConfigBreadcrumb section="servicios" variant="nuevo" />

      <main className="flex-1 p-6 md:p-8">
        <div className="mx-auto flex max-w-[720px] flex-col gap-6">
          <div>
            <h1 className="page-title">Añadir nuevo servicio</h1>
          </div>
          <ServicioForm mode="create" />
        </div>
      </main>
    </div>
  );
}
