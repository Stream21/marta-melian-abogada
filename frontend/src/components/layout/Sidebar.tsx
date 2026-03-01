import { useState } from 'react';
import { Link, useRouter } from '@tanstack/react-router';
import {
  LayoutDashboard,
  FolderOpen,
  Calendar,
  CalendarCheck,
  Users,
  Settings,
  LogOut,
  PanelLeftClose,
  PanelLeftOpen,
  ChevronDown,
  X,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useAuth } from '@/contexts/AuthContext';

export interface SidebarProps {
  collapsed: boolean;
  mobileOpen: boolean;
  onToggle: () => void;
  onMobileClose: () => void;
}

const navItems = [
  { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
  { label: 'Listado de Expedientes', href: '/expedientes', icon: FolderOpen },
  { label: 'Calendario de Vencimientos', href: '/agenda', icon: Calendar },
  { label: 'Agenda (Citas)', href: '/agenda', icon: CalendarCheck },
  { label: 'Clientes', href: '/clientes', icon: Users },
];

const configSubItems = [
  { label: 'Categorías', href: '/expedientes' },
  { label: 'Recursos', href: '/expedientes' },
  { label: 'Servicios', href: '/expedientes' },
  { label: 'Usuarios y Roles', href: '/expedientes' },
];

interface ContentProps {
  collapsed: boolean;
  configOpen: boolean;
  setConfigOpen: (v: boolean) => void;
  onLogout: () => void;
  onToggle?: () => void;
}

function SidebarContent({ collapsed, configOpen, setConfigOpen, onLogout, onToggle }: ContentProps) {
  return (
    <div className="flex h-full flex-col">
      <div className={cn('flex items-center justify-center px-4 py-4 shrink-0', collapsed && 'py-3')}>
        <img
          src="/logo.png"
          alt="Bufete Melián"
          className={cn('object-contain', collapsed ? 'h-10 w-10' : 'h-24 w-auto max-w-[200px]')}
        />
      </div>

      <div className="mx-4 h-px bg-blue-800/50 mb-4" />

      <nav className="flex-1 overflow-y-auto overflow-x-hidden px-3 space-y-0.5 scrollbar-none"
        style={{ scrollbarWidth: 'none' }}
      >
        {navItems.map((item) => (
          <Link
            key={item.label}
            to={item.href}
            className={cn(
              'flex items-center gap-3 rounded-lg px-3 py-2.5 text-blue-100 hover:text-white hover:bg-white/5 transition-colors',
              '[&.active]:bg-white/10 [&.active]:text-white',
              collapsed && 'justify-center px-2',
            )}
            title={collapsed ? item.label : undefined}
          >
            <item.icon className="h-[22px] w-[22px] shrink-0" />
            {!collapsed && <span className="text-sm font-medium">{item.label}</span>}
          </Link>
        ))}

        <div className="mt-2">
          <button
            onClick={() => !collapsed && setConfigOpen(!configOpen)}
            className={cn(
              'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-blue-100 hover:text-white hover:bg-white/5 transition-colors',
              collapsed && 'justify-center px-2',
            )}
            title={collapsed ? 'Configuración' : undefined}
          >
            <Settings className="h-[22px] w-[22px] shrink-0" />
            {!collapsed && (
              <>
                <span className="text-sm font-medium flex-1 text-left">Configuración</span>
                <ChevronDown
                  className={cn('h-4 w-4 transition-transform duration-200', configOpen && 'rotate-180')}
                />
              </>
            )}
          </button>

          {!collapsed && configOpen && (
            <div className="ml-6 mt-1 pl-3 border-l border-white/10 space-y-0.5">
              <p className="px-3 pt-2 pb-1 text-[10px] font-bold text-blue-300/60 uppercase tracking-widest">
                Expediente
              </p>
              {configSubItems.map((sub) => (
                <Link
                  key={sub.label}
                  to={sub.href}
                  className="flex items-center gap-3 rounded-lg px-3 py-1.5 text-blue-200 hover:text-white hover:bg-white/5 transition-colors"
                >
                  <span className="h-1.5 w-1.5 rounded-full bg-blue-400 shrink-0" />
                  <span className="text-sm font-normal">{sub.label}</span>
                </Link>
              ))}
            </div>
          )}
        </div>
      </nav>

      <div className="border-t border-blue-800/50 bg-blue-900/20 px-3 py-3 shrink-0">
        {collapsed ? (
          <div className="flex flex-col items-center gap-1">
            {onToggle && (
              <button
                onClick={onToggle}
                aria-label="Expandir sidebar"
                className="rounded-lg p-2 text-white/40 hover:text-white hover:bg-white/5 transition-colors"
              >
                <PanelLeftOpen className="h-5 w-5" />
              </button>
            )}
            <button
              onClick={onLogout}
              title="Cerrar Sesión"
              className="rounded-lg p-2 text-white/40 hover:text-white hover:bg-white/5 transition-colors"
            >
              <LogOut className="h-5 w-5" />
            </button>
          </div>
        ) : (
          <div className="flex items-center justify-between">
            <button
              onClick={onLogout}
              className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-blue-100 hover:text-white hover:bg-white/5 transition-colors"
            >
              <LogOut className="h-[18px] w-[18px] shrink-0" />
              <span className="text-sm font-medium">Cerrar Sesión</span>
            </button>
            {onToggle && (
              <button
                onClick={onToggle}
                aria-label="Colapsar sidebar"
                className="rounded-lg p-2 text-white/30 hover:text-white/80 hover:bg-white/5 transition-colors"
              >
                <PanelLeftClose className="h-4 w-4" />
              </button>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

export function Sidebar({ collapsed, mobileOpen, onToggle, onMobileClose }: SidebarProps) {
  const { logout } = useAuth();
  const router = useRouter();
  const [configOpen, setConfigOpen] = useState(true);

  const handleLogout = () => {
    logout();
    void router.navigate({ to: '/login', replace: true });
  };

  const contentProps: ContentProps = {
    collapsed,
    configOpen,
    setConfigOpen,
    onLogout: handleLogout,
    onToggle,
  };

  return (
    <>
      {/* Desktop sidebar */}
      <aside
        className={cn(
          'hidden lg:flex flex-col h-screen bg-[#1e3a8a] text-white flex-shrink-0 shadow-xl relative transition-all duration-300 ease-in-out z-10',
          collapsed ? 'w-16' : 'w-72',
        )}
      >
        <SidebarContent {...contentProps} />
      </aside>

      {/* Mobile sidebar */}
      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-30 flex flex-col w-72 bg-[#1e3a8a] text-white shadow-2xl lg:hidden transition-transform duration-300 ease-in-out',
          mobileOpen ? 'translate-x-0' : '-translate-x-full',
        )}
      >
        <button
          onClick={onMobileClose}
          aria-label="Cerrar menú"
          className="absolute right-4 top-4 text-blue-200 hover:text-white transition-colors z-10 p-1"
        >
          <X className="h-5 w-5" />
        </button>
        <SidebarContent {...contentProps} collapsed={false} />
      </aside>
    </>
  );
}
