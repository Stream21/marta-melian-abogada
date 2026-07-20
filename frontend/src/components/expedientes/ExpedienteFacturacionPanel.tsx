import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  AlertTriangle,
  Banknote,
  CheckCircle2,
  Clock,
  Copy,
  CreditCard,
  FileText,
  Loader2,
  Mail,
  MessageCircle,
  RefreshCw,
} from 'lucide-react';
import { api, type CobroExpedienteResponse, type PaymentHoldedEstado } from '@/api/client';
import { useMercureContratacion } from '@/hooks/useMercureContratacion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { MetricCards } from '@/components/expedientes/MetricCards';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

interface ExpedienteFacturacionPanelProps {
  expedienteId: string;
}

const fmt = (n: number) =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n);

function estadoBadgeVariant(estado: CobroExpedienteResponse['estado']) {
  switch (estado) {
    case 'pagado':
      return 'success' as const;
    case 'vencido':
      return 'destructive' as const;
    case 'enlace_pendiente':
      return 'warning' as const;
    default:
      return 'secondary' as const;
  }
}

const holdedVariant: Record<
  PaymentHoldedEstado,
  'default' | 'secondary' | 'success' | 'warning' | 'destructive'
> = {
  sincronizado: 'success',
  pendiente_sync: 'warning',
  error: 'destructive',
  no_aplica: 'secondary',
};

function HoldedFacturaActions({
  holdedEstado,
  holdedEstadoLabel,
  holdedSyncError,
  paymentId,
  pdfUrl,
  onAction,
  compact = false,
}: {
  holdedEstado?: PaymentHoldedEstado | null;
  holdedEstadoLabel?: string | null;
  holdedSyncError?: string | null;
  paymentId?: string | null;
  pdfUrl?: string | null;
  onAction: () => void;
  compact?: boolean;
}) {
  const syncMutation = useMutation({
    mutationFn: () => api.sincronizarPagoHolded(paymentId!),
    onSuccess: () => onAction(),
  });

  if (!holdedEstado || holdedEstado === 'no_aplica') {
    return null;
  }

  const canSync =
    !!paymentId && (holdedEstado === 'pendiente_sync' || holdedEstado === 'error');
  const canDownload = holdedEstado === 'sincronizado' && !!pdfUrl;

  return (
    <div className={cn('flex flex-col gap-2', compact ? 'items-start' : 'items-end')}>
      <Badge variant={holdedVariant[holdedEstado]}>{holdedEstadoLabel ?? holdedEstado}</Badge>
      {holdedSyncError && (
        <p
          className={cn(
            'flex items-start gap-1 text-xs text-destructive max-w-[220px]',
            compact ? 'text-left' : 'text-right',
          )}
        >
          <AlertTriangle className="h-3 w-3 shrink-0 mt-0.5" />
          <span className="line-clamp-2">{holdedSyncError}</span>
        </p>
      )}
      <div className="flex flex-wrap items-center gap-2">
        {canSync && (
          <Button
            variant="outline"
            size="sm"
            disabled={syncMutation.isPending}
            onClick={() => syncMutation.mutate()}
          >
            {syncMutation.isPending ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <RefreshCw className="mr-2 h-4 w-4" />
            )}
            Reintentar Holded
          </Button>
        )}
        {canDownload && (
          <Button variant="outline" size="sm" asChild>
            <a href={pdfUrl} target="_blank" rel="noreferrer">
              <FileText className="mr-2 h-4 w-4" />
              Ver factura
            </a>
          </Button>
        )}
      </div>
    </div>
  );
}

function CobroCard({
  cobro,
  expedienteId,
  contacto,
  onAction,
}: {
  cobro: CobroExpedienteResponse;
  expedienteId: string;
  contacto: { telefono: string; email: string };
  onAction: () => void;
}) {
  const [dialog, setDialog] = useState<'manual' | 'enlace' | null>(null);
  const [phone, setPhone] = useState(contacto.telefono);
  const [email, setEmail] = useState(contacto.email);
  const [enlaceGenerado, setEnlaceGenerado] = useState<string | null>(null);

  const puedeCobrar = cobro.estado === 'pendiente' || cobro.estado === 'vencido' || cobro.estado === 'enlace_pendiente';

  const manualMutation = useMutation({
    mutationFn: () =>
      api.postPaymentManual({
        expedienteId,
        amount: String(cobro.importe),
        cuotaNumero: cobro.numero,
      }),
    onSuccess: () => {
      setDialog(null);
      onAction();
    },
  });

  const enlaceMutation = useMutation({
    mutationFn: () =>
      api.postPaymentGenerateLink({
        expedienteId,
        amount: String(cobro.importe),
        phone: phone.trim(),
        email: email.trim(),
        cuotaNumero: cobro.numero,
      }),
    onSuccess: (data) => {
      if (data.url) setEnlaceGenerado(data.url);
      onAction();
    },
  });

  const copiarEnlace = async () => {
    if (!enlaceGenerado) return;
    await navigator.clipboard.writeText(enlaceGenerado);
  };

  return (
    <>
      <div
        className={cn(
          'rounded-xl border p-5 space-y-4',
          cobro.estado === 'pagado' && 'border-emerald-200 bg-emerald-50/30',
          cobro.estado === 'vencido' && 'border-red-200 bg-red-50/20',
          cobro.estado === 'enlace_pendiente' && 'border-amber-200 bg-amber-50/30',
        )}
      >
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <span className="font-semibold">
                Cuota {cobro.numero}
                {cobro.numero === 1 ? ' — Pago inicial' : ''}
              </span>
              <Badge variant={estadoBadgeVariant(cobro.estado)}>{cobro.estadoLabel}</Badge>
            </div>
            <p className="mt-1 text-2xl font-bold text-foreground">{fmt(cobro.importe)}</p>
            {cobro.fechaVencimiento && (
              <p className="text-sm text-muted-foreground mt-1">
                Vencimiento:{' '}
                {new Date(cobro.fechaVencimiento + 'T12:00:00').toLocaleDateString('es-ES', {
                  day: 'numeric',
                  month: 'long',
                  year: 'numeric',
                })}
              </p>
            )}
          </div>
          {cobro.estado === 'pagado' && (
            <HoldedFacturaActions
              holdedEstado={cobro.holdedEstado}
              holdedEstadoLabel={cobro.holdedEstadoLabel}
              holdedSyncError={cobro.holdedSyncError}
              paymentId={cobro.paymentId}
              pdfUrl={cobro.pdfUrl}
              onAction={onAction}
            />
          )}
        </div>

        {puedeCobrar && cobro.estado !== 'pagado' && (
          <div className="flex flex-wrap gap-2 pt-1">
            <Button
              size="sm"
              variant="outline"
              className="border-orange-200 bg-orange-50/50 hover:bg-orange-50"
              onClick={() => setDialog('manual')}
            >
              <Banknote className="mr-2 h-4 w-4" />
              Marcar cobro manual
            </Button>
            <Button
              size="sm"
              className="bg-violet-600 hover:bg-violet-700 text-white"
              onClick={() => {
                setEnlaceGenerado(null);
                setDialog('enlace');
              }}
            >
              <CreditCard className="mr-2 h-4 w-4" />
              Enlace de pago
            </Button>
          </div>
        )}

        {cobro.estado === 'enlace_pendiente' && (
          <p className="text-xs text-amber-800 bg-amber-50 rounded-md px-3 py-2 border border-amber-100">
            Enlace enviado al cliente. El cobro se actualizará automáticamente cuando pague por Stripe.
          </p>
        )}
      </div>

      <Dialog open={dialog === 'manual'} onOpenChange={(o) => !o && setDialog(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Registrar cobro manual</DialogTitle>
            <DialogDescription>
              Se generará la factura en Holded y se marcará la cuota {cobro.numero} como cobrada (
              {fmt(cobro.importe)}).
            </DialogDescription>
          </DialogHeader>
          <DialogFooter className="gap-2 sm:gap-0">
            <Button variant="outline" onClick={() => setDialog(null)}>
              Cancelar
            </Button>
            <Button
              onClick={() => manualMutation.mutate()}
              disabled={manualMutation.isPending}
              className="bg-orange-500 hover:bg-orange-600 text-white"
            >
              {manualMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Confirmar cobro
            </Button>
          </DialogFooter>
          {manualMutation.isError && (
            <p className="text-sm text-destructive">{manualMutation.error.message}</p>
          )}
        </DialogContent>
      </Dialog>

      <Dialog open={dialog === 'enlace'} onOpenChange={(o) => !o && setDialog(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Enviar enlace de pago</DialogTitle>
            <DialogDescription>
              Genere un enlace Stripe de {fmt(cobro.importe)} para la cuota {cobro.numero}. Envíelo por WhatsApp,
              email o cópielo manualmente.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-2">
            <div className="space-y-2">
              <Label htmlFor={`phone-${cobro.numero}`}>
                <MessageCircle className="inline h-3.5 w-3.5 mr-1" />
                WhatsApp
              </Label>
              <Input
                id={`phone-${cobro.numero}`}
                type="tel"
                placeholder="+34 600 000 000"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor={`email-${cobro.numero}`}>
                <Mail className="inline h-3.5 w-3.5 mr-1" />
                Email
              </Label>
              <Input
                id={`email-${cobro.numero}`}
                type="email"
                placeholder="cliente@email.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>
            {!phone.trim() && !email.trim() && (
              <p className="text-xs text-muted-foreground">Indique al menos un canal de envío o genere y copie el enlace.</p>
            )}
          </div>
          {enlaceGenerado && (
            <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 flex items-start gap-2">
              <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" />
              <div className="min-w-0 flex-1">
                <p className="text-xs font-medium text-emerald-800">Enlace generado</p>
                <p className="text-xs text-emerald-700 truncate mt-1">{enlaceGenerado}</p>
              </div>
              <Button type="button" variant="ghost" size="icon" className="shrink-0 h-8 w-8" onClick={copiarEnlace}>
                <Copy className="h-4 w-4" />
              </Button>
            </div>
          )}
          <DialogFooter className="gap-2 sm:gap-0">
            <Button variant="outline" onClick={() => setDialog(null)}>
              Cerrar
            </Button>
            <Button
              onClick={() => enlaceMutation.mutate()}
              disabled={enlaceMutation.isPending || (!phone.trim() && !email.trim() && !enlaceGenerado)}
              className="bg-violet-600 hover:bg-violet-700 text-white"
            >
              {enlaceMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {enlaceGenerado ? 'Reenviar enlace' : 'Generar y enviar'}
            </Button>
          </DialogFooter>
          {enlaceMutation.isError && (
            <p className="text-sm text-destructive">{enlaceMutation.error.message}</p>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}

export function ExpedienteFacturacionPanel({ expedienteId }: ExpedienteFacturacionPanelProps) {
  useMercureContratacion(expedienteId);

  const queryClient = useQueryClient();
  const { data, isLoading, error } = useQuery({
    queryKey: ['facturacion', expedienteId],
    queryFn: () => api.getFacturacionExpediente(expedienteId),
    refetchInterval: 15000,
  });

  const syncAllMutation = useMutation({
    mutationFn: () => api.sincronizarCobrosExpedienteHolded(expedienteId),
    onSuccess: () => refrescar(),
  });

  const refrescar = () => {
    void queryClient.invalidateQueries({ queryKey: ['facturacion', expedienteId] });
    void queryClient.invalidateQueries({ queryKey: ['payments', expedienteId] });
    void queryClient.invalidateQueries({ queryKey: ['notificaciones'] });
    void queryClient.invalidateQueries({ queryKey: ['cobros-globales'] });
  };

  if (isLoading) {
    return <p className="text-muted-foreground py-12 text-center">Cargando facturación…</p>;
  }

  if (error || !data) {
    return (
      <p className="text-destructive py-12 text-center">No se pudo cargar la información de facturación.</p>
    );
  }

  const { resumen, cobros, holdedResumen } = data;
  const cobrosHoldedPendientes = holdedResumen.pendientes + holdedResumen.errores;

  return (
    <div className="space-y-6">
      <div>
        <p className="section-label">Cobros del expediente</p>
        <h2 className="page-title text-xl">Facturación y cobros</h2>
        <p className="page-subtitle mt-1">
          Calendario acordado con el cliente — {data.numCuotas} cuota{data.numCuotas !== 1 ? 's' : ''} ·{' '}
          {data.metodoPago === 'manual' ? 'Cobro manual' : 'Pasarela Stripe'}
        </p>
      </div>

      {holdedResumen.requiereAccion && (
        <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950 space-y-3">
          <div className="flex items-start gap-2">
            <AlertTriangle className="h-4 w-4 shrink-0 mt-0.5" />
            <div>
              <p className="font-medium">
                {cobrosHoldedPendientes} cobro{cobrosHoldedPendientes !== 1 ? 's' : ''} pagado
                {cobrosHoldedPendientes !== 1 ? 's' : ''} sin factura en Holded
              </p>
              <p className="mt-1 text-amber-800">
                El cobro quedó registrado en la app, pero la factura no se generó en Holded. Puede
                reintentar la sincronización de uno en uno o procesar todos a la vez.
              </p>
              {holdedResumen.errores > 0 && (
                <p className="mt-1 text-amber-800">
                  {holdedResumen.errores} cobro{holdedResumen.errores !== 1 ? 's' : ''} con error
                  tras reintento manual.
                </p>
              )}
            </div>
          </div>
          <Button
            size="sm"
            className="bg-amber-600 hover:bg-amber-700 text-white"
            disabled={syncAllMutation.isPending}
            onClick={() => syncAllMutation.mutate()}
          >
            {syncAllMutation.isPending ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <RefreshCw className="mr-2 h-4 w-4" />
            )}
            Sincronizar todos con Holded
          </Button>
          {syncAllMutation.isError && (
            <p className="text-destructive text-xs">{syncAllMutation.error.message}</p>
          )}
          {syncAllMutation.data && !syncAllMutation.data.success && (
            <p className="text-destructive text-xs">
              {syncAllMutation.data.fallidos} de {syncAllMutation.data.total} cobros no se
              sincronizaron.
            </p>
          )}
        </div>
      )}

      <MetricCards
        totalExpediente={resumen.total}
        totalCobrado={resumen.cobrado}
        pendiente={resumen.pendiente}
      />

      {resumen.vencido > 0 && (
        <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 flex items-center gap-2">
          <Clock className="h-4 w-4 shrink-0" />
          Hay {fmt(resumen.vencido)} en cuotas vencidas pendientes de cobro.
        </div>
      )}

      <Separator />

      <div className="space-y-4">
        <h3 className="text-base font-semibold">Calendario de cobros</h3>
        {cobros.length === 0 ? (
          <div className="panel p-8 text-center text-sm text-muted-foreground">
            No hay cuotas configuradas. Revise las condiciones de pago en la fase de contratación.
          </div>
        ) : (
          <div className="grid gap-4">
            {cobros.map((cobro) => (
              <CobroCard
                key={cobro.numero}
                cobro={cobro}
                expedienteId={expedienteId}
                contacto={data.contacto}
                onAction={refrescar}
              />
            ))}
          </div>
        )}
      </div>

      {data.historialPagos.length > 0 && (
        <>
          <Separator />
          <div className="space-y-3">
            <h3 className="text-base font-semibold">Historial de pagos</h3>
            <div className="panel divide-y">
              {data.historialPagos.map((pago) => (
                <div key={pago.id} className="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                  <div>
                    <span className="font-medium">{fmt(parseFloat(pago.amount))}</span>
                    {pago.cuotaNumero != null && (
                      <span className="text-muted-foreground ml-2">· Cuota {pago.cuotaNumero}</span>
                    )}
                    <p className="text-xs text-muted-foreground mt-0.5">
                      {pago.type === 'manual' ? 'Cobro manual' : 'Stripe'} ·{' '}
                      {new Date(pago.createdAt).toLocaleString('es-ES')}
                    </p>
                  </div>
                  <div className="flex flex-wrap items-center gap-2">
                    <Badge variant={pago.status === 'paid' ? 'success' : 'secondary'}>
                      {pago.status === 'paid' ? 'Cobrado' : 'Pendiente'}
                    </Badge>
                    {pago.holdedEstado && pago.holdedEstado !== 'no_aplica' && (
                      <HoldedFacturaActions
                        holdedEstado={pago.holdedEstado as PaymentHoldedEstado}
                        holdedEstadoLabel={pago.holdedEstadoLabel}
                        holdedSyncError={pago.holdedSyncError}
                        paymentId={pago.id}
                        pdfUrl={pago.pdfUrl}
                        onAction={refrescar}
                        compact
                      />
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </>
      )}
    </div>
  );
}
