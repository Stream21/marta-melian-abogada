import type { ReactNode } from 'react';
import { CheckCircle2 } from 'lucide-react';
import type { AccesoExpedienteResponse, AccesoPasoResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

interface PortalClienteShellProps {
  data: AccesoExpedienteResponse;
  children: ReactNode;
}

export function PortalClienteShell({ data, children }: PortalClienteShellProps) {
  const logoUrl = data.despachoLogoUrl ?? '/logo.png';
  const nombre =
    data.clienteNombre?.trim() ||
    data.clienteDatos?.nombre?.trim() ||
    'Su expediente';

  return (
    <div className="min-h-screen bg-muted/30 pb-8">
      <header className="sticky top-0 z-20 border-b border-border bg-card/95 backdrop-blur supports-[backdrop-filter]:bg-card/80">
        <div className="mx-auto flex max-w-2xl items-center gap-3 px-4 py-3">
          <img
            src={logoUrl}
            alt="Bufete Melián"
            className="h-11 w-auto max-w-[120px] shrink-0 object-contain object-left"
          />
          <div className="min-w-0 flex-1">
            <p className="truncate text-base font-semibold leading-tight text-foreground">{nombre}</p>
            <p className="mt-0.5 truncate text-xs text-muted-foreground">
              {data.expedienteNumero} · {data.tramiteNombre}
            </p>
          </div>
          <Badge variant="info" className="hidden shrink-0 sm:inline-flex">
            {data.faseNegocioLabel}
          </Badge>
        </div>
      </header>

      <main className="mx-auto w-full max-w-2xl px-4 pt-4">
        <div className="mb-4 flex items-center justify-between gap-2 sm:hidden">
          <Badge variant="info">{data.faseNegocioLabel}</Badge>
        </div>

        {data.pasos && data.pasos.length > 0 && (
          <ProgresoContratacion pasos={data.pasos} pasoActivo={data.pasoActivo} />
        )}

        <div className="panel p-4 sm:p-6">{children}</div>
      </main>
    </div>
  );
}

function ProgresoContratacion({
  pasos,
  pasoActivo,
}: {
  pasos: AccesoPasoResponse[];
  pasoActivo?: string | null;
}) {
  const indiceActivo = Math.max(
    0,
    pasos.findIndex((p) => p.paso === pasoActivo),
  );
  const pasoActual = pasos[indiceActivo] ?? pasos[0];
  const completados = pasos.filter((p) => p.estado === 'validado_abogado').length;
  const progreso = pasos.length > 0 ? ((completados + (pasoActivo ? 0.5 : 0)) / pasos.length) * 100 : 0;

  return (
    <div className="mb-4 space-y-3">
      {/* Móvil: barra + paso actual */}
      <div className="sm:hidden">
        <div className="flex items-center justify-between text-xs text-muted-foreground">
          <span>
            Paso {indiceActivo + 1} de {pasos.length}
          </span>
          <span className="font-medium text-foreground">{pasoActual?.label}</span>
        </div>
        <div className="mt-2 h-2 overflow-hidden rounded-full bg-muted">
          <div
            className="h-full rounded-full bg-primary transition-all duration-300"
            style={{ width: `${Math.min(100, Math.max(8, progreso))}%` }}
          />
        </div>
      </div>

      {/* Escritorio: stepper clásico */}
      <div className="hidden sm:flex items-center justify-between gap-1 overflow-x-auto pb-1">
        {pasos.map((paso, i) => {
          const activo = paso.paso === pasoActivo;
          const completado = paso.estado === 'validado_abogado';
          const enRevision = paso.estado === 'realizado_cliente';

          return (
            <div key={paso.paso} className="flex min-w-0 flex-1 items-center">
              <div className="flex flex-1 flex-col items-center gap-1">
                <div
                  className={cn(
                    'flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-bold',
                    completado && 'border-emerald-500 bg-emerald-500 text-white',
                    enRevision && 'border-amber-500 bg-amber-100 text-amber-800',
                    activo && !completado && !enRevision && 'border-primary bg-primary/10 text-primary',
                    !activo && !completado && !enRevision && 'border-border text-muted-foreground',
                  )}
                >
                  {completado ? <CheckCircle2 className="h-4 w-4" /> : i + 1}
                </div>
                <span className="w-full truncate px-1 text-center text-[10px] font-medium text-muted-foreground">
                  {paso.label}
                </span>
              </div>
              {i < pasos.length - 1 && (
                <div className={cn('mx-1 h-0.5 w-4 shrink-0', completado ? 'bg-emerald-400' : 'bg-border')} />
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
