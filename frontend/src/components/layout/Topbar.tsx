import { useState } from 'react';
import {
  Bell,
  Search,
  Menu,
  X,
  AlertTriangle,
  Banknote,
  PenLine,
  CheckCircle,
  CalendarCheck,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useAuth } from '@/contexts/AuthContext';

export interface TopbarProps {
  onMobileMenuToggle: () => void;
}

interface Notificacion {
  icon: React.ElementType;
  iconBg: string;
  iconColor: string;
  title: string;
  sub?: string;
  time: string;
}

const notificaciones: Notificacion[] = [
  {
    icon: PenLine,
    iconBg: 'bg-blue-100',
    iconColor: 'text-blue-700',
    title: 'Carlos Ruiz firmó Hoja de Encargo',
    time: 'Hace 15 min',
  },
  {
    icon: AlertTriangle,
    iconBg: 'bg-orange-100',
    iconColor: 'text-orange-700',
    title: 'Notificación de resolución recibida',
    sub: 'Expediente #3302 - Extranjería',
    time: 'Hace 1h',
  },
  {
    icon: Banknote,
    iconBg: 'bg-emerald-100',
    iconColor: 'text-emerald-700',
    title: 'Ana Belén realizó el pago de cuota 2/12',
    time: 'Hace 2h',
  },
  {
    icon: CheckCircle,
    iconBg: 'bg-indigo-100',
    iconColor: 'text-indigo-700',
    title: 'Documentos validados por Administrativo',
    time: 'Hace 3h',
  },
  {
    icon: CalendarCheck,
    iconBg: 'bg-purple-100',
    iconColor: 'text-purple-700',
    title: 'Nueva cita agendada por cliente',
    sub: 'Mañana a las 10:30 - Presencial',
    time: 'Hace 5h',
  },
];

export function Topbar({ onMobileMenuToggle }: TopbarProps) {
  const [notifOpen, setNotifOpen] = useState(false);
  const { userEmail } = useAuth();

  const displayName = userEmail ?? 'Usuario';
  const initials = displayName.charAt(0).toUpperCase();

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

        <div className="flex items-center gap-4">
          <button
            onClick={() => setNotifOpen((p) => !p)}
            aria-label="Notificaciones"
            className="relative text-muted-foreground hover:text-primary transition-colors p-2 hover:bg-muted rounded-full"
          >
            <Bell className="h-5 w-5" />
            <span className="absolute top-2 right-2 h-2 w-2 rounded-full bg-destructive ring-2 ring-card" />
          </button>

          <div className="h-8 w-px bg-border" />

          <div className="flex items-center gap-2.5">
            <div className="h-9 w-9 rounded-full bg-primary flex items-center justify-center text-primary-foreground text-sm font-bold shrink-0">
              {initials}
            </div>
            <div className="hidden md:flex flex-col">
              <span className="text-sm font-bold text-foreground leading-none">{displayName}</span>
              <span className="text-[11px] text-muted-foreground font-medium">Socio Principal</span>
            </div>
          </div>
        </div>
      </header>

      {/* Notifications panel */}
      <div
        className={cn(
          'fixed inset-y-0 right-0 z-40 w-80 bg-card shadow-2xl border-l flex flex-col transition-transform duration-300 ease-in-out',
          notifOpen ? 'translate-x-0' : 'translate-x-full',
        )}
      >
        <div className="flex items-center justify-between p-5 border-b shrink-0">
          <div className="flex items-center gap-2">
            <h3 className="text-base font-bold text-foreground">Notificaciones</h3>
            <span className="text-[11px] font-bold bg-destructive text-destructive-foreground px-1.5 py-0.5 rounded-full">
              {notificaciones.length}
            </span>
          </div>
          <button
            onClick={() => setNotifOpen(false)}
            aria-label="Cerrar notificaciones"
            className="text-muted-foreground hover:text-foreground transition-colors p-1 rounded-md hover:bg-muted"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-5">
          <div className="relative">
            <div className="absolute left-4 top-2 bottom-2 w-px bg-border" />
            <div className="flex flex-col gap-6">
              {notificaciones.map((n, i) => (
                <div key={i} className="flex gap-4 relative">
                  <div
                    className={cn(
                      'size-8 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-card ring-1 ring-border shadow-sm',
                      n.iconBg,
                      n.iconColor,
                    )}
                  >
                    <n.icon className="h-4 w-4" />
                  </div>
                  <div className="flex flex-col pb-2">
                    <p className="text-sm text-foreground">{n.title}</p>
                    {n.sub && <p className="text-[11px] text-muted-foreground mt-1">{n.sub}</p>}
                    <span className="text-[10px] text-muted-foreground/60 mt-1.5 font-bold uppercase tracking-wide">
                      {n.time}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="panel-footer">
          <button className="link-brand">
            Ver todas las notificaciones
          </button>
        </div>
      </div>

      {notifOpen && (
        <div className="fixed inset-0 z-30 bg-black/20" onClick={() => setNotifOpen(false)} />
      )}
    </>
  );
}
