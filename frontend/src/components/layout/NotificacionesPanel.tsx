import { useEffect, useRef, useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import {
  Bell,
  BellOff,
  ChevronRight,
  FileText,
  Loader2,
  PenLine,
  CheckCircle,
  User,
  AlertTriangle,
  X,
} from 'lucide-react';
import type { NotificacionResponse } from '@/api/client';
import { buildExpedienteNotificacionSearch } from '@/lib/notificacion-destino';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

function formatRelativeTime(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return 'Ahora';
  if (mins < 60) return `Hace ${mins} min`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `Hace ${hours} h`;
  return new Date(iso).toLocaleDateString('es-ES', {
    day: 'numeric',
    month: 'short',
  });
}

function resumenNotificacion(descripcion: string): string {
  const limpio = descripcion.replace(/\s+/g, ' ').trim();
  if (limpio.length <= 160) return limpio;
  return `${limpio.slice(0, 157)}…`;
}

function notificacionVisual(n: NotificacionResponse) {
  if (n.tipo.includes('holded_sync')) {
    return { icon: AlertTriangle, iconBg: 'bg-amber-100', iconColor: 'text-amber-700' };
  }
  if (n.tipo.includes('requerimientos')) {
    return { icon: FileText, iconBg: 'bg-violet-100', iconColor: 'text-violet-700' };
  }
  if (n.tipo.includes('firma') || n.tipo.includes('paso_completado')) {
    return { icon: PenLine, iconBg: 'bg-blue-100', iconColor: 'text-blue-700' };
  }
  if (n.tipo.includes('validado') || n.tipo.includes('fase_completada')) {
    return { icon: CheckCircle, iconBg: 'bg-emerald-100', iconColor: 'text-emerald-700' };
  }
  return { icon: User, iconBg: 'bg-indigo-100', iconColor: 'text-indigo-700' };
}

export interface NotificacionesPanelProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  notificaciones: NotificacionResponse[];
  totalPendientes: number;
  badgeLabel: string | null;
  isLoading: boolean;
  onMarcarLeida: (id: string) => Promise<void>;
  onMarcarTodasLeidas: () => Promise<void>;
}

export function NotificacionesPanel({
  open,
  onOpenChange,
  notificaciones,
  totalPendientes,
  badgeLabel,
  isLoading,
  onMarcarLeida,
  onMarcarTodasLeidas,
}: NotificacionesPanelProps) {
  const navigate = useNavigate();
  const panelRef = useRef<HTMLDivElement>(null);
  const [marcandoTodas, setMarcandoTodas] = useState(false);

  useEffect(() => {
    if (!open) return;

    const onPointerDown = (event: MouseEvent) => {
      if (panelRef.current && !panelRef.current.contains(event.target as Node)) {
        onOpenChange(false);
      }
    };

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onOpenChange(false);
      }
    };

    document.addEventListener('mousedown', onPointerDown);
    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('mousedown', onPointerDown);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, [open, onOpenChange]);

  const handleClick = (n: NotificacionResponse) => {
    onOpenChange(false);
    void onMarcarLeida(n.id);
    void navigate({
      to: '/expedientes/$expedienteId',
      params: { expedienteId: n.expedienteId },
      search: buildExpedienteNotificacionSearch(n),
    });
  };

  const handleMarcarTodas = async () => {
    setMarcandoTodas(true);
    try {
      await onMarcarTodasLeidas();
    } finally {
      setMarcandoTodas(false);
    }
  };

  return (
    <div className="relative" ref={panelRef}>
      <button
        type="button"
        onClick={() => onOpenChange(!open)}
        className={cn(
          'relative rounded-lg p-2 transition-colors',
          open
            ? 'bg-primary/10 text-primary'
            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
        )}
        aria-label="Notificaciones"
        aria-expanded={open}
      >
        <Bell className="h-5 w-5" />
        {badgeLabel && (
          <span className="absolute -right-0.5 -top-0.5 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold leading-none text-white ring-2 ring-card">
            {badgeLabel}
          </span>
        )}
      </button>

      {open && (
        <div
          className={cn(
            'absolute right-0 top-[calc(100%+0.5rem)] z-50 flex w-[min(28rem,calc(100vw-1.5rem))] flex-col overflow-hidden rounded-2xl border border-border bg-card shadow-2xl',
            'animate-in fade-in-0 zoom-in-95 slide-in-from-top-2 duration-200',
          )}
        >
          <div className="flex items-start justify-between gap-3 border-b bg-muted/30 px-5 py-4">
            <div className="min-w-0">
              <h3 className="text-base font-semibold text-foreground">Notificaciones</h3>
              <p className="mt-0.5 text-xs text-muted-foreground">
                {totalPendientes === 0
                  ? 'Está al día con la actividad del bufete'
                  : `${totalPendientes} pendiente${totalPendientes !== 1 ? 's' : ''} de revisión`}
              </p>
            </div>
            <div className="flex shrink-0 items-center gap-1">
              {totalPendientes > 0 && (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="h-8 px-2 text-xs text-primary hover:bg-primary/10 hover:text-primary"
                  disabled={marcandoTodas}
                  onClick={() => void handleMarcarTodas()}
                >
                  {marcandoTodas ? (
                    <>
                      <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                      Marcando…
                    </>
                  ) : (
                    'Marcar todas'
                  )}
                </Button>
              )}
              <button
                type="button"
                onClick={() => onOpenChange(false)}
                className="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                aria-label="Cerrar"
              >
                <X className="h-4 w-4" />
              </button>
            </div>
          </div>

          <div className="max-h-[min(32rem,70vh)] overflow-y-auto overflow-x-hidden overscroll-contain">
            {isLoading && notificaciones.length === 0 ? (
              <div className="flex items-center justify-center gap-2 px-5 py-12 text-sm text-muted-foreground">
                <Loader2 className="h-4 w-4 animate-spin" />
                Cargando…
              </div>
            ) : notificaciones.length === 0 ? (
              <div className="flex flex-col items-center gap-3 px-6 py-14 text-center">
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                  <BellOff className="h-5 w-5 text-muted-foreground" />
                </div>
                <div>
                  <p className="text-sm font-medium text-foreground">Sin notificaciones pendientes</p>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Cuando el cliente actúe en un expediente, aparecerá aquí.
                  </p>
                </div>
              </div>
            ) : (
              <ul className="divide-y divide-border">
                {notificaciones.map((n) => {
                  const visual = notificacionVisual(n);
                  const Icon = visual.icon;
                  return (
                    <li key={n.id}>
                      <button
                        type="button"
                        onClick={() => handleClick(n)}
                        className="group flex w-full gap-4 px-5 py-4 text-left transition-colors hover:bg-primary/5"
                      >
                        <div className="relative mt-0.5 shrink-0">
                          <div
                            className={cn(
                              'flex h-10 w-10 items-center justify-center rounded-xl',
                              visual.iconBg,
                            )}
                          >
                            <Icon className={cn('h-4 w-4', visual.iconColor)} />
                          </div>
                          <span className="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-primary ring-2 ring-card" />
                        </div>

                        <div className="min-w-0 flex-1">
                          <p className="line-clamp-2 text-sm font-medium leading-snug text-foreground">
                            {resumenNotificacion(n.descripcion)}
                          </p>
                          <div className="mt-2 flex flex-wrap items-center gap-2">
                            <Badge variant="secondary" className="font-mono text-[10px]">
                              {n.expedienteNumero}
                            </Badge>
                            <span className="truncate text-xs text-muted-foreground">{n.clienteNombre}</span>
                          </div>
                          <p className="mt-1.5 text-[11px] font-medium uppercase tracking-wide text-muted-foreground/70">
                            {formatRelativeTime(n.createdAt)}
                          </p>
                        </div>

                        <ChevronRight className="mt-2 h-4 w-4 shrink-0 text-muted-foreground/40 transition-transform group-hover:translate-x-0.5 group-hover:text-primary" />
                      </button>
                    </li>
                  );
                })}
              </ul>
            )}
          </div>

        </div>
      )}
    </div>
  );
}
