import { CheckCircle2, Clock } from 'lucide-react';
import type { AccesoExpedienteResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { docEntregado } from './types';

interface ContratacionResumenFinalProps {
  data: AccesoExpedienteResponse;
  /**
   * Tras enviar al despacho: cabecera de espera + mismo resumen debajo
   * (una sola pantalla, sin temporizador).
   */
  esperandoAbogado?: boolean;
}

export function ContratacionResumenFinal({
  data,
  esperandoAbogado = false,
}: ContratacionResumenFinalProps) {
  const pasos = data.pasos ?? [];
  const docs = (data.documentosRequeridos ?? []).filter((d) => d.obligatorio);
  const firmas = data.documentosFirma ?? [];
  const pago = data.resumenPago;

  return (
    <div className="space-y-6">
      {esperandoAbogado ? (
        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-center">
          <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
            <Clock className="h-6 w-6 animate-pulse text-amber-700 motion-reduce:animate-none" />
          </div>
          <h2 className="text-lg font-semibold text-foreground">Esperando a su abogada</h2>
          <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
            El despacho ya tiene su información. Revisarán lo enviado y le avisarán cuando pueda
            continuar. Mientras tanto, aquí tiene el resumen de lo gestionado.
          </p>
        </div>
      ) : (
        <div>
          <p className="section-label">Contratación</p>
          <h2 className="mt-1 text-xl font-semibold text-foreground">Resumen de lo gestionado</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            Revisa lo completado. Su abogado finalizará la contratación cuando corresponda.
          </p>
        </div>
      )}

      {pasos.length > 0 && (
        <section className="space-y-2">
          <h3 className="text-sm font-semibold text-foreground">Subfases de contratación</h3>
          <ul className="space-y-2">
            {pasos.map((paso, index) => (
              <li
                key={paso.paso}
                className="flex items-center justify-between gap-3 rounded-lg border border-border px-3 py-2.5 text-sm"
              >
                <span className="font-medium">
                  <span className="mr-1.5 tabular-nums text-muted-foreground">
                    {index + 1}/{pasos.length}
                  </span>
                  {paso.label}
                </span>
                <Badge
                  variant={
                    paso.estado === 'validado_abogado'
                      ? 'success'
                      : paso.estado === 'realizado_cliente'
                        ? 'warning'
                        : 'secondary'
                  }
                >
                  {paso.estadoLabel}
                </Badge>
              </li>
            ))}
          </ul>
        </section>
      )}

      <section className="space-y-2">
        <h3 className="text-sm font-semibold text-foreground">Identidad</h3>
        <div className="rounded-lg border border-border px-3 py-2.5 text-sm">
          <p className="font-medium text-foreground">
            {data.clienteNombre?.trim() || data.clienteDatos?.nombre || 'Datos del cliente'}
          </p>
          {data.clienteDatos?.numDocumento && (
            <p className="mt-0.5 text-muted-foreground">{data.clienteDatos.numDocumento}</p>
          )}
        </div>
      </section>

      {docs.length > 0 && (
        <section className="space-y-2">
          <h3 className="text-sm font-semibold text-foreground">Documentación</h3>
          <ul className="space-y-2">
            {docs.map((doc) => (
              <ResumenFila
                key={doc.id}
                label={doc.nombre}
                ok={docEntregado(doc)}
                okLabel="Entregado"
                pendingLabel="Pendiente"
              />
            ))}
          </ul>
        </section>
      )}

      {firmas.length > 0 && (
        <section className="space-y-2">
          <h3 className="text-sm font-semibold text-foreground">Firmas</h3>
          <ul className="space-y-2">
            {firmas.map((doc) => (
              <ResumenFila
                key={doc.tipo}
                label={doc.label}
                ok={!!doc.firmado}
                okLabel="Firmado"
                pendingLabel="Pendiente"
              />
            ))}
          </ul>
        </section>
      )}

      {pago && (
        <section className="space-y-2">
          <h3 className="text-sm font-semibold text-foreground">Pago</h3>
          <div className="rounded-lg border border-border px-3 py-2.5 text-sm">
            <p className="font-medium">{pago.metodoPagoLabel}</p>
            <p className="mt-0.5 text-muted-foreground">
              {pago.planPagoLabel ??
                (pago.planPago === 'fraccionado'
                  ? `Fraccionado (${pago.numCuotas} cuotas)`
                  : 'Pago único')}
            </p>
          </div>
        </section>
      )}
    </div>
  );
}

function ResumenFila({
  label,
  ok,
  okLabel,
  pendingLabel,
}: {
  label: string;
  ok: boolean;
  okLabel: string;
  pendingLabel: string;
}) {
  return (
    <li className="flex items-center justify-between gap-3 rounded-lg border border-border px-3 py-2.5 text-sm">
      <span className="min-w-0 truncate font-medium">{label}</span>
      <Badge variant={ok ? 'success' : 'secondary'} className="gap-1 shrink-0">
        {ok ? <CheckCircle2 className="h-3 w-3" /> : null}
        {ok ? okLabel : pendingLabel}
      </Badge>
    </li>
  );
}
