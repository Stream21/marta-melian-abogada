import { useState } from 'react';
import { Link, useRouter } from '@tanstack/react-router';
import {
  FolderOpen,
  Users,
  Receipt,
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
  { label: 'Expedientes', href: '/expedientes', icon: FolderOpen },
  { label: 'Clientes', href: '/clientes', icon: Users },
  { label: 'Facturación', href: '/facturacion', icon: Receipt },
];

const configSubItems = [
  { label: 'Servicios', href: '/config/servicios' },
  { label: 'Trámites', href: '/config/tramites' },
  { label: 'Datos del despacho', href: '/config/despacho' },
];

interface ContentProps {
  collapsed: boolean;
  configOpen: boolean;
  setConfigOpen: (v: boolean) => void;
  onLogout: () => void;
  onToggle?: () => void;
}

const navLinkClass =
  'flex items-center gap-3 rounded-lg px-3 py-2.5 text-primary-foreground/70 hover:text-primary-foreground hover:bg-primary-foreground/5 transition-colors [&.active]:bg-primary-foreground/10 [&.active]:text-primary-foreground';

const iconBtnClass =
  'rounded-lg p-2 text-primary-foreground/40 hover:text-primary-foreground hover:bg-primary-foreground/5 transition-colors';

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

      <div className="mx-4 h-px bg-primary-foreground/10 mb-4" />

      <nav className="flex-1 overflow-y-auto overflow-x-hidden px-3 space-y-0.5 scrollbar-none"
        style={{ scrollbarWidth: 'none' }}
      >
        {navItems.map((item) => (
          <Link
            key={item.label}
            to={item.href}
            className={cn(navLinkClass, collapsed && 'justify-center px-2')}
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
              'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-primary-foreground/70 hover:text-primary-foreground hover:bg-primary-foreground/5 transition-colors',
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
            <div className="ml-6 mt-1 pl-3 border-l border-primary-foreground/10 space-y-0.5">
              {configSubItems.map((sub) => (
                <Link
                  key={sub.label}
                  to={sub.href}
                  className="flex items-center gap-3 rounded-lg px-3 py-1.5 text-primary-foreground/60 hover:text-primary-foreground hover:bg-primary-foreground/5 transition-colors"
                >
                  <span className="h-1.5 w-1.5 rounded-full bg-primary-foreground/50 shrink-0" />
                  <span className="text-sm font-normal">{sub.label}</span>
                </Link>
              ))}
            </div>
          )}
        </div>
      </nav>

      <div className="border-t border-primary-foreground/10 bg-black/10 px-3 py-3 shrink-0">
        {collapsed ? (
          <div className="flex flex-col items-center gap-1">
            {onToggle && (
              <button onClick={onToggle} aria-label="Expandir sidebar" className={iconBtnClass}>
                <PanelLeftOpen className="h-5 w-5" />
              </button>
            )}
            <button onClick={onLogout} title="Cerrar Sesión" className={iconBtnClass}>
              <LogOut className="h-5 w-5" />
            </button>
          </div>
        ) : (
          <div className="flex items-center justify-between">
            <button
              onClick={onLogout}
              className={navLinkClass}
            >
              <LogOut className="h-[18px] w-[18px] shrink-0" />
              <span className="text-sm font-medium">Cerrar Sesión</span>
            </button>
            {onToggle && (
              <button onClick={onToggle} aria-label="Colapsar sidebar" className={iconBtnClass}>
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
      <aside
        className={cn(
          'hidden lg:flex flex-col h-screen bg-primary text-primary-foreground flex-shrink-0 shadow-xl relative transition-all duration-300 ease-in-out z-10',
          collapsed ? 'w-16' : 'w-72',
        )}
      >
        <SidebarContent {...contentProps} />
      </aside>

      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-30 flex flex-col w-72 bg-primary text-primary-foreground shadow-2xl lg:hidden transition-transform duration-300 ease-in-out',
          mobileOpen ? 'translate-x-0' : '-translate-x-full',
        )}
      >
        <button
          onClick={onMobileClose}
          aria-label="Cerrar menú"
          className="absolute right-4 top-4 text-primary-foreground/60 hover:text-primary-foreground transition-colors z-10 p-1"
        >
          <X className="h-5 w-5" />
        </button>
        <SidebarContent {...contentProps} collapsed={false} />
      </aside>
    </>
  );
}
