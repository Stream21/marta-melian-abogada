import { useEffect, useState } from 'react';
import { Menu } from 'lucide-react';
import { useMercureAbogado } from '@/hooks/useMercureAbogado';
import { useNotificaciones } from '@/hooks/useNotificaciones';
import { GlobalSearchBar } from '@/components/layout/GlobalSearchBar';
import { NotificacionesPanel } from '@/components/layout/NotificacionesPanel';
import { useAuth } from '@/contexts/AuthContext';

export interface TopbarProps {
  onMobileMenuToggle: () => void;
}

export function Topbar({ onMobileMenuToggle }: TopbarProps) {
  useMercureAbogado();
  const [notifOpen, setNotifOpen] = useState(false);
  const [searchFocusId, setSearchFocusId] = useState(0);
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
  const searchShortcut =
    typeof navigator !== 'undefined' && /Mac|iPhone|iPad/i.test(navigator.userAgent)
      ? '⌘K'
      : 'Ctrl+K';

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent) => {
      const isModK = (event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k';
      if (!isModK) return;
      const target = event.target as HTMLElement | null;
      if (target?.closest('input, textarea, [contenteditable="true"]')) {
        // Si ya está en el buscador, no interferir; si está en otro campo, sí enfocar búsqueda.
        if (target.getAttribute('role') === 'combobox') return;
      }
      event.preventDefault();
      setSearchFocusId((n) => n + 1);
    };
    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, []);

  return (
    <header className="relative z-20 flex h-16 shrink-0 items-center gap-3 border-b bg-card px-4 shadow-sm md:px-8">
      <button
        onClick={onMobileMenuToggle}
        aria-label="Abrir menú"
        className="shrink-0 rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground lg:hidden"
      >
        <Menu className="h-5 w-5" />
      </button>

      <GlobalSearchBar
        className="min-w-0 flex-1"
        focusRequestId={searchFocusId}
        shortcutLabel={searchShortcut}
      />

      <div className="flex shrink-0 items-center gap-3">
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
