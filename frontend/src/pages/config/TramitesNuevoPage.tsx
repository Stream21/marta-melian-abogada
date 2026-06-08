import { ConfigBreadcrumb } from '@/components/config/ConfigBreadcrumb';
import { TramiteForm } from '@/components/config/TramiteForm';

export function TramitesNuevoPage() {
  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <ConfigBreadcrumb section="tramites" variant="nuevo" />

      <main className="flex-1 p-6 md:p-8">
        <div className="mx-auto flex max-w-[720px] flex-col gap-6">
          <div>
            <h1 className="page-title">Añadir nuevo trámite</h1>
          </div>

          <TramiteForm mode="create" />
        </div>
      </main>
    </div>
  );
}
