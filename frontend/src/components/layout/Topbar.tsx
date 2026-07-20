import { useState } from 'react';
import { Menu } from 'lucide-react';
import { useMercureAbogado } from '@/hooks/useMercureAbogado';
import { useNotificaciones } from '@/hooks/useNotificaciones';
import { NotificacionesPanel } from '@/components/layout/NotificacionesPanel';
import { useAuth } from '@/contexts/AuthContext';

export interface TopbarProps {
  onMobileMenuToggle: () => void;
}

export function Topbar({ onMobileMenuToggle }: TopbarProps) {
  useMercureAbogado();
  const [notifOpen, setNotifOpen] = useState(false);
  const { userEmail } = useAuth();
  const {
    notificaciones,
    totalPendientes,
    badgeLabel,
    isLoading,
    marcarLeida,
    marcarTodasLeidas,
  } = useNotificaciones();

  const displayName = userEmail ?? 'Usuario';
  const initials = displayName.charAt(0).toUpperCase();

  return (
    <header className="relative z-20 flex h-16 shrink-0 items-center justify-between border-b bg-card px-4 shadow-sm md:px-8">
      <div className="flex items-center gap-3">
        <button
          onClick={onMobileMenuToggle}
          aria-label="Abrir menú"
          className="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground lg:hidden"
        >
          <Menu className="h-5 w-5" />
        </button>
      </div>

      <div className="flex items-center gap-3">
        <NotificacionesPanel
          open={notifOpen}
          onOpenChange={setNotifOpen}
          notificaciones={notificaciones}
          totalPendientes={totalPendientes}
          badgeLabel={badgeLabel}
          isLoading={isLoading}
          onMarcarLeida={marcarLeida}
          onMarcarTodasLeidas={marcarTodasLeidas}
        />

        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
          {initials}
        </div>
      </div>
    </header>
  );
}
