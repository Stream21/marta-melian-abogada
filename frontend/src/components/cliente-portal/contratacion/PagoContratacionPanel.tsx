import { Loader2 } from 'lucide-react';
import type { AccesoExpedienteResponse } from '@/api/client';
import { Button } from '@/components/ui/button';
import { CalendarioCuotasTable } from '@/components/expedientes/contratacion/CondicionesPagoPanel';
import { formatEuros, getImportePagoInicial } from '@/lib/pago-contratacion';
import { cn } from '@/lib/utils';

interface PagoContratacionPanelProps {
  resumen: NonNullable<AccesoExpedienteResponse['resumenPago']>;
  onIniciarPago: () => void;
  iniciando: boolean;
}

export function PagoContratacionPanel({
  resumen,
  onIniciarPago,
  iniciando,
}: PagoContratacionPanelProps) {
  const calendario = resumen.calendarioPago ?? resumen.calendarioProyectado ?? [];
  const calendarioDefinitivo = !!resumen.calendarioPago && !!resumen.fechaFirmaContrato;
  const importePagoInicial = getImportePagoInicial(resumen);
  const planLabel =
    resumen.planPago === 'fraccionado'
      ? `Fraccionado (${resumen.numCuotas} cuotas)`
      : 'Pago único';
  const esManual = resumen.metodoPago === 'manual';

  return (
    <div className="space-y-4">
      <div className="space-y-3 rounded-lg border bg-card p-4 text-sm">
        <DatoFila
          label={resumen.planPago === 'fraccionado' ? 'Pago inicial (1.ª cuota)' : 'Importe a pagar'}
          value={formatEuros(importePagoInicial)}
          destacado
        />
        {resumen.planPago === 'fraccionado' && (
          <DatoFila label="Honorarios totales" value={formatEuros(resumen.honorariosAcordados)} />
        )}
        <DatoFila label="Método" value={resumen.metodoPagoLabel} />
        <DatoFila label="Plan" value={resumen.planPagoLabel ?? planLabel} />
        {esManual && resumen.iban && (
          <>
            <DatoFila label="Titular" value={resumen.titularCuenta} />
            <DatoFila label="IBAN" value={resumen.iban} />
          </>
        )}
        {resumen.metodoPago === 'digital' && (
          <Button className="mt-2 min-h-[44px] w-full" size="lg" onClick={onIniciarPago} disabled={iniciando}>
            {iniciando ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Redirigiendo…
              </>
            ) : (
              'Pagar ahora'
            )}
          </Button>
        )}
      </div>

      {esManual && (
        <div className="rounded-lg border border-border bg-muted/20 p-4 text-sm text-muted-foreground">
          <p className="font-medium text-foreground">¿Cómo funciona el pago manual?</p>
          <p className="mt-2">
            Realice el pago inicial según las instrucciones anteriores. No debe confirmar nada en este
            portal: su abogado validará el cobro cuando lo reciba.
          </p>
        </div>
      )}

      {calendario.length > 0 && (
        <CalendarioCuotasTable
          cuotas={calendario}
          definitivo={calendarioDefinitivo}
          fechaFirmaContrato={resumen.fechaFirmaContrato}
        />
      )}
    </div>
  );
}

function DatoFila({
  label,
  value,
  destacado,
}: {
  label: string;
  value: string;
  destacado?: boolean;
}) {
  return (
    <div className="flex justify-between gap-4">
      <span className="text-muted-foreground">{label}</span>
      <span className={cn('text-right', destacado && 'font-bold text-primary')}>{value}</span>
    </div>
  );
}
