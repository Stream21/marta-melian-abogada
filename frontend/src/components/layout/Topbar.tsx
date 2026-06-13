import { useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import { Bell, Search, Menu, X, PenLine, CheckCircle, User } from 'lucide-react';
import { api, type NotificacionResponse } from '@/api/client';
import { useMercureAbogado } from '@/hooks/useMercureAbogado';
import { cn } from '@/lib/utils';
import { useAuth } from '@/contexts/AuthContext';

export interface TopbarProps {
  onMobileMenuToggle: () => void;
}

function formatRelativeTime(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return 'Ahora';
  if (mins < 60) return `Hace ${mins} min`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `Hace ${hours} h`;
  return new Date(iso).toLocaleDateString('es-ES');
}

function notificacionVisual(n: NotificacionResponse) {
  if (n.tipo.includes('firma') || n.tipo.includes('paso_completado')) {
    return { icon: PenLine, iconBg: 'bg-blue-100', iconColor: 'text-blue-700' };
  }
  if (n.tipo.includes('validado') || n.tipo.includes('fase_completada')) {
    return { icon: CheckCircle, iconBg: 'bg-emerald-100', iconColor: 'text-emerald-700' };
  }
  return { icon: User, iconBg: 'bg-indigo-100', iconColor: 'text-indigo-700' };
}

export function Topbar({ onMobileMenuToggle }: TopbarProps) {
  useMercureAbogado();
  const navigate = useNavigate();
  const [notifOpen, setNotifOpen] = useState(false);
  const { userEmail } = useAuth();

  const { data: notificaciones = [] } = useQuery({
    queryKey: ['notificaciones'],
    queryFn: () => api.getNotificacionesRecientes(),
    refetchInterval: 60_000,
  });

  const displayName = userEmail ?? 'Usuario';
  const initials = displayName.charAt(0).toUpperCase();
  const pendientes = notificaciones.filter((n) => n.actor === 'cliente').length;

  return (
    <>
      <header className="flex h-16 shrink-0 items-center justify-between border-b bg-card px-4 md:px-8 z-20 shadow-sm relative">
        <div className="flex items-center gap-3">
          <button
            onClick={onMobileMenuToggle}
            aria-label="Abrir menú"
            className="text-muted-foreground hover:text-foreground transition-colors lg:hidden p-1.5 rounded-md hover:bg-muted"
          >
            <Menu className="h-5 w-5" />
          </button>
          <h2 className="text-primary text-xl font-bold tracking-tight">Panel de Control</h2>
        </div>

        <div className="flex-1 max-w-xl px-6 hidden md:block">
          <div className="relative group">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground group-focus-within:text-primary transition-colors" />
            <input
              type="text"
              placeholder="Buscar expedientes o clientes..."
              className="input-field pl-10 pr-3"
            />
          </div>
        </div>

        <div className="flex items-center gap-3">
          <div className="relative">
            <button
              onClick={() => setNotifOpen((p) => !p)}
              className="relative rounded-lg p-2 text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
              aria-label="Notificaciones"
            >
              <Bell className="h-5 w-5" />
              {pendientes > 0 && (
                <span className="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-destructive text-[10px] font-bold text-white">
                  {pendientes > 9 ? '9+' : pendientes}
                </span>
              )}
            </button>

            {notifOpen && (
              <>
                <div className="fixed inset-0 z-30" onClick={() => setNotifOpen(false)} />
                <div className="absolute right-0 top-full z-40 mt-2 w-80 rounded-xl border bg-card shadow-lg">
                  <div className="flex items-center justify-between border-b px-4 py-3">
                    <h3 className="font-semibold text-sm">Notificaciones</h3>
                    <button onClick={() => setNotifOpen(false)} className="text-muted-foreground hover:text-foreground">
                      <X className="h-4 w-4" />
                    </button>
                  </div>
                  <div className="max-h-80 overflow-y-auto">
                    {notificaciones.length === 0 ? (
                      <p className="p-4 text-sm text-muted-foreground">Sin notificaciones recientes.</p>
                    ) : (
                      notificaciones.map((n) => {
                        const visual = notificacionVisual(n);
                        const Icon = visual.icon;
                        return (
                          <button
                            key={n.id}
                            type="button"
                            onClick={() => {
                              setNotifOpen(false);
                              void navigate({
                                to: '/expedientes/$expedienteId',
                                params: { expedienteId: n.expedienteId },
                              });
                            }}
                            className="flex w-full gap-3 border-b px-4 py-3 last:border-0 hover:bg-muted/50 text-left"
                          >
                            <div className={cn('flex h-9 w-9 shrink-0 items-center justify-center rounded-full', visual.iconBg)}>
                              <Icon className={cn('h-4 w-4', visual.iconColor)} />
                            </div>
                            <div className="min-w-0">
                              <p className="text-sm font-medium leading-snug">{n.descripcion}</p>
                              <p className="text-xs text-muted-foreground mt-0.5">
                                {n.expedienteNumero} · {n.clienteNombre}
                              </p>
                              <p className="text-xs text-muted-foreground mt-0.5">
                                {formatRelativeTime(n.createdAt)}
                              </p>
                            </div>
                          </button>
                        );
                      })
                    )}
                  </div>
                </div>
              </>
            )}
          </div>

          <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
            {initials}
          </div>
        </div>
      </header>
    </>
  );
}
