import { FileText } from 'lucide-react';
import type { AccesoExpedienteResponse } from '@/api/client';
import { RequerimientosUploadPanel } from '@/components/cliente-portal/RequerimientosUploadPanel';
import { cn } from '@/lib/utils';

interface RequerimientosClientePortalProps {
  token: string;
  data: AccesoExpedienteResponse;
}

export function RequerimientosClientePortal({ token, data }: RequerimientosClientePortalProps) {
  const req = data.requerimientos;

  if (!req) {
    return (
      <div className="py-8 text-center text-sm text-muted-foreground">
        Cargando documentación requerida…
      </div>
    );
  }

  const total = req.documentos.length;
  const completados = req.documentos.filter((d) => d.estado === 'validado').length;
  const pct = total > 0 ? Math.round((completados / total) * 100) : 0;
  const pendientesCliente = req.pendientesSubida;
  const todoListo = pendientesCliente === 0 && req.enRevision === 0 && completados === total && total > 0;

  return (
    <div className="space-y-5">
      <header className="space-y-4">
        <div className="flex items-start gap-3">
          <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground">
            <FileText className="h-5 w-5" />
          </div>
          <div className="min-w-0">
            <h2 className="text-xl font-semibold tracking-tight text-foreground">
              Documentación requerida
            </h2>
            <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
              Suba cada documento pendiente. Cuando pulse «Listo», su abogado lo revisará.
            </p>
          </div>
        </div>

        {total > 0 && (
          <div
            className={cn(
              'rounded-xl border p-4',
              todoListo
                ? 'border-emerald-200 bg-emerald-50/60'
                : pendientesCliente > 0
                  ? 'border-primary/25 bg-primary/5'
                  : 'border-amber-200 bg-amber-50/50',
            )}
          >
            <div className="flex flex-wrap items-end justify-between gap-2">
              <div>
                <p className="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                  Progreso
                </p>
                <p className="mt-1 text-lg font-semibold text-foreground">
                  {todoListo
                    ? 'Toda la documentación está completa'
                    : pendientesCliente > 0
                      ? `${pendientesCliente} pendiente${pendientesCliente !== 1 ? 's' : ''} de su parte`
                      : `${req.enRevision} en revisión por su abogado`}
                </p>
              </div>
              <p className="text-sm font-medium tabular-nums text-muted-foreground">
                {completados}/{total} validados
              </p>
            </div>
            <div className="mt-3 h-2 overflow-hidden rounded-full bg-muted">
              <div
                className={cn(
                  'h-full rounded-full transition-all duration-300',
                  todoListo ? 'bg-emerald-500' : 'bg-primary',
                )}
                style={{ width: `${pct}%` }}
              />
            </div>
          </div>
        )}
      </header>

      <RequerimientosUploadPanel token={token} documentos={req.documentos} />
    </div>
  );
}
