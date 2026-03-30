import { ConfigExpedienteBreadcrumb } from '@/components/config/ConfigExpedienteBreadcrumb';
import { TipoCasoForm } from '@/components/config/TipoCasoForm';

export function TiposCasoNuevoPage() {
  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <ConfigExpedienteBreadcrumb variant="nuevo" />

      <main className="flex-1 p-6 md:p-8">
        <div className="mx-auto flex max-w-[720px] flex-col gap-6">
          <div className="flex gap-3">
            <div>
              <h1 className="page-title">Añadir Nuevo Tipo de Caso</h1>
              <p className="page-subtitle">
                Defina un nuevo tipo de caso legal. Esta clasificación se utilizará para organizar los
                expedientes.
              </p>
            </div>
          </div>

          <TipoCasoForm mode="create" />
        </div>
      </main>
    </div>
  );
}
