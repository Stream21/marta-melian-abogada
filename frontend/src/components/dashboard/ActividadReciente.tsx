import { Link } from '@tanstack/react-router';
import {
  History,
  PenLine,
  AlertTriangle,
  FileText,
  CheckCircle,
  User,
} from 'lucide-react';
import type { NotificacionResponse } from '@/api/client';
import {
  buildExpedienteNotificacionSearch,
  type ExpedienteNotificacionSearch,
} from '@/lib/notificacion-destino';
import { cn } from '@/lib/utils';

interface ActividadRecienteProps {
  items: NotificacionResponse[];
  totalSinLeer: number;
}

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

function notificacionVisual(n: NotificacionResponse) {
  if (n.tipo.includes('holded_sync')) {
    return { icon: AlertTriangle, iconBg: 'bg-amber-100', iconColor: 'text-amber-700' };
  }
  if (n.tipo.includes('pago') || n.tipo.includes('stripe')) {
    return { icon: CheckCircle, iconBg: 'bg-emerald-100', iconColor: 'text-emerald-700' };
  }
  if (n.tipo.includes('documentacion') || n.tipo.includes('requerimientos')) {
    return { icon: FileText, iconBg: 'bg-violet-100', iconColor: 'text-violet-700' };
  }
  if (n.tipo.includes('firma') || n.tipo.includes('paso_completado')) {
    return { icon: PenLine, iconBg: 'bg-blue-100', iconColor: 'text-blue-700' };
  }
  if (n.actor === 'cliente') {
    return { icon: User, iconBg: 'bg-orange-100', iconColor: 'text-orange-700' };
  }
  return { icon: History, iconBg: 'bg-muted', iconColor: 'text-muted-foreground' };
}

export function ActividadReciente({ items, totalSinLeer }: ActividadRecienteProps) {
  return (
    <div className="panel flex flex-col h-full">
      <div className="panel-header justify-between">
        <div className="flex items-center gap-3">
          <div className="panel-header-icon">
            <History className="h-5 w-5" />
          </div>
          <div>
            <h3 className="panel-title">Actividad reciente</h3>
            {totalSinLeer > 0 && (
              <p className="text-xs text-muted-foreground mt-0.5">
                {totalSinLeer} sin leer
              </p>
            )}
          </div>
        </div>
      </div>

      <div className="p-6 flex-1 overflow-y-auto">
        {items.length === 0 ? (
          <p className="text-sm text-muted-foreground text-center py-8">
            No hay notificaciones pendientes.
          </p>
        ) : (
          <div className="relative">
            <div className="absolute left-4 top-2 bottom-2 w-px bg-border" />
            <div className="flex flex-col gap-6">
              {items.map((n) => {
                const visual = notificacionVisual(n);
                const Icon = visual.icon;
                const search: ExpedienteNotificacionSearch = buildExpedienteNotificacionSearch(n);
                return (
                  <Link
                    key={n.id}
                    to="/expedientes/$expedienteId"
                    params={{ expedienteId: n.expedienteId }}
                    search={search}
                    className="flex gap-4 relative group"
                  >
                    <div
                      className={cn(
                        'size-8 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-card ring-1 ring-border shadow-sm',
                        visual.iconBg,
                        visual.iconColor,
                      )}
                    >
                      <Icon className="h-4 w-4" />
                    </div>
                    <div className="flex flex-col pb-2 min-w-0">
                      <p className="text-sm text-foreground group-hover:text-primary transition-colors line-clamp-2">
                        {n.descripcion}
                      </p>
                      <p className="text-[11px] text-muted-foreground mt-1 truncate">
                        {n.clienteNombre}
                        {n.expedienteNumero ? ` · ${n.expedienteNumero}` : ''}
                      </p>
                      <span className="text-[10px] text-muted-foreground/60 mt-1.5 font-bold uppercase tracking-wide">
                        {formatRelativeTime(n.createdAt)}
                      </span>
                    </div>
                  </Link>
                );
              })}
            </div>
          </div>
        )}
      </div>

      <div className="panel-footer">
        <Link to="/expedientes" className="link-brand">
          Ir a expedientes
        </Link>
      </div>
    </div>
  );
}
