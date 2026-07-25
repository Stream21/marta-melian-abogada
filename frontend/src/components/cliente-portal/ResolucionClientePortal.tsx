import {
  Bell,
  CalendarClock,
  CheckCircle2,
  ExternalLink,
  FileText,
  Scale,
  ThumbsDown,
  ThumbsUp,
} from 'lucide-react';
import type { AccesoExpedienteResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

interface ResolucionClientePortalProps {
  data: AccesoExpedienteResponse;
}

export function ResolucionClientePortal({ data }: ResolucionClientePortalProps) {
  const resolucion = data.resolucion;
  if (!resolucion) {
    return (
      <p className="py-8 text-center text-sm text-muted-foreground">
        La información de resolución aún no está disponible.
      </p>
    );
  }

  const r = resolucion.resolucion;
  const concedida = r?.outcome === 'concedida';
  const hechas = r?.gestiones.filter((g) => g.hecho).length ?? 0;
  const total = r?.gestiones.length ?? 0;

  return (
    <div className="space-y-6">
      <section
        className={cn(
          'overflow-hidden rounded-xl border shadow-sm',
          !r && 'border-border bg-card',
          r && concedida && 'border-emerald-200 bg-emerald-50',
          r && !concedida && 'border-destructive/25 bg-destructive/5',
        )}
      >
        <div className="space-y-4 p-5 sm:p-6">
          <div className="flex items-start gap-3">
            <div
              className={cn(
                'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl',
                !r && 'bg-muted text-primary',
                r && concedida && 'bg-emerald-100 text-emerald-700',
                r && !concedida && 'bg-destructive/10 text-destructive',
              )}
            >
              {!r && <Scale className="h-6 w-6" />}
              {r && concedida && <ThumbsUp className="h-6 w-6" />}
              {r && !concedida && <ThumbsDown className="h-6 w-6" />}
            </div>
            <div className="min-w-0">
              <p className="section-label">Estado de su solicitud</p>
              <h2 className="mt-1 text-xl font-semibold text-foreground sm:text-2xl">
                {resolucion.estadoClienteLabel}
              </h2>
              {!r && (
                <p className="mt-2 text-sm text-muted-foreground">
                  Su abogado está a la espera de la resolución administrativa. Le avisaremos cuando
                  esté disponible.
                </p>
              )}
              {r && (
                <p className="mt-2 text-sm text-muted-foreground">
                  {concedida
                    ? 'La Administración ha concedido su solicitud. Revise los pasos siguientes para completar el proceso.'
                    : 'La Administración ha denegado su solicitud. Consulte con su abogado los posibles recursos.'}
                </p>
              )}
            </div>
          </div>

          {r && (
            <div className="flex flex-wrap items-center gap-2 border-t border-border/60 pt-4">
              <Badge variant={concedida ? 'success' : 'destructive'} className="gap-1">
                {r.outcomeLabel}
              </Badge>
              <span className="text-sm text-muted-foreground">
                Notificada el{' '}
                {new Date(r.fechaNotificacion + 'T12:00:00').toLocaleDateString('es-ES')}
              </span>
            </div>
          )}
        </div>
      </section>

      {r && (
        <section className="rounded-xl border border-border bg-card p-5 shadow-sm">
          <div className="flex items-start gap-3">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted text-primary">
              <FileText className="h-5 w-5" />
            </div>
            <div>
              <h3 className="font-semibold">Documento de resolución</h3>
              <p className="mt-1 text-sm text-muted-foreground">
                Su abogado dispone del PDF oficial. Si necesita una copia, solicítela al despacho.
              </p>
            </div>
          </div>
        </section>
      )}

      {r && total > 0 && (
        <section className="space-y-4">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h3 className="text-lg font-semibold">Pasos siguientes</h3>
              <p className="mt-0.5 text-sm text-muted-foreground">
                Gestiones orientativas según su tipo de trámite.
              </p>
            </div>
            <p className="text-sm font-medium text-muted-foreground">
              {hechas} / {total} completados
            </p>
          </div>

          <div className="h-2 overflow-hidden rounded-full bg-muted">
            <div
              className="h-full rounded-full bg-primary transition-all"
              style={{ width: `${Math.round((hechas / total) * 100)}%` }}
            />
          </div>

          <ol className="space-y-3">
            {r.gestiones.map((g) => (
              <li
                key={g.id}
                className={cn(
                  'rounded-xl border bg-card p-4 shadow-sm transition-colors',
                  g.hecho ? 'border-primary/25 bg-primary/5' : 'border-border',
                )}
              >
                <div className="flex items-start gap-3">
                  {g.hecho ? (
                    <CheckCircle2 className="mt-0.5 h-6 w-6 shrink-0 text-primary" />
                  ) : (
                    <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-primary/30 text-xs font-bold text-primary">
                      {g.orden}
                    </span>
                  )}
                  <div className="min-w-0 flex-1">
                    <p className={cn('font-semibold', g.hecho && 'text-muted-foreground')}>
                      {g.titulo}
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">{g.descripcion}</p>
                    {g.url && (
                      <a
                        href={g.url}
                        target="_blank"
                        rel="noreferrer"
                        className="mt-3 inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground"
                      >
                        <ExternalLink className="h-4 w-4" />
                        Abrir enlace oficial
                      </a>
                    )}
                  </div>
                </div>
              </li>
            ))}
          </ol>
        </section>
      )}

      {resolucion.recordatorio && (
        <section className="overflow-hidden rounded-xl border border-primary/20 bg-primary/5 shadow-sm">
          <div className="flex items-start gap-3 p-5">
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
              <Bell className="h-5 w-5" />
            </div>
            <div>
              <h3 className="font-semibold">Recordatorio programado</h3>
              <p className="mt-2 flex flex-wrap items-center gap-2 text-sm">
                <CalendarClock className="h-4 w-4 text-primary" />
                <span className="font-medium">
                  {new Date(resolucion.recordatorio.fecha + 'T12:00:00').toLocaleDateString('es-ES', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                  })}
                </span>
              </p>
              {(resolucion.recordatorio.servicioNombre ||
                resolucion.recordatorio.tramiteNombre) && (
                <div className="mt-2 space-y-0.5 text-sm text-muted-foreground">
                  {resolucion.recordatorio.servicioNombre && (
                    <p>Servicio: {resolucion.recordatorio.servicioNombre}</p>
                  )}
                  {resolucion.recordatorio.tramiteNombre && (
                    <p>Trámite: {resolucion.recordatorio.tramiteNombre}</p>
                  )}
                </div>
              )}
              {!resolucion.recordatorio.servicioNombre && (
                <p className="mt-1 text-sm text-muted-foreground">{resolucion.recordatorio.motivo}</p>
              )}
            </div>
          </div>
        </section>
      )}
    </div>
  );
}
