import { Link, useRouter } from '@tanstack/react-router';
import { FolderOpen, Users, Calendar, Receipt, Settings, Scale, LogOut } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useAuth } from '@/contexts/AuthContext';

const navItems = [
  { label: 'Expedientes', href: '/expedientes', icon: FolderOpen },
  { label: 'Clientes', href: '/clientes', icon: Users },
  { label: 'Agenda', href: '/agenda', icon: Calendar },
];

const bottomNavItems = [
  { label: 'Facturación', href: '/expedientes', icon: Receipt },
  { label: 'Configuración', href: '/expedientes', icon: Settings },
];

export function Sidebar() {
  const { userEmail, logout } = useAuth();
  const router = useRouter();

  const handleLogout = () => {
    logout();
    void router.navigate({ to: '/login', replace: true });
  };

  const initials = userEmail ? userEmail.charAt(0).toUpperCase() : 'U';
  const displayName = userEmail ?? 'Usuario';

  return (
    <aside className="flex h-screen w-60 flex-col bg-[#0f172a] text-white flex-shrink-0">
      <div className="flex items-center gap-2.5 px-5 py-5 border-b border-white/10">
        <div className="flex h-8 w-8 items-center justify-center rounded-md bg-blue-500">
          <Scale className="h-4 w-4 text-white" />
        </div>
        <span className="text-base font-semibold tracking-tight">Bufete Marta</span>
      </div>

      <nav className="flex-1 px-3 py-4 space-y-1">
        <p className="px-2 pb-1 text-[10px] font-semibold uppercase tracking-widest text-slate-500">
          Principal
        </p>
        {navItems.map((item) => (
          <NavLink key={item.href} {...item} />
        ))}
      </nav>

      <div className="px-3 pb-4 space-y-1">
        <p className="px-2 pb-1 text-[10px] font-semibold uppercase tracking-widest text-slate-500">
          Gestión
        </p>
        {bottomNavItems.map((item) => (
          <NavLink key={item.label} {...item} />
        ))}
      </div>

      <div className="border-t border-white/10 px-4 py-4">
        <div className="flex items-center gap-3">
          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold">
            {initials}
          </div>
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-medium">{displayName}</p>
          </div>
          <button
            type="button"
            onClick={handleLogout}
            title="Cerrar sesión"
            className="text-slate-400 hover:text-white transition-colors"
          >
            <LogOut className="h-4 w-4" />
          </button>
        </div>
      </div>
    </aside>
  );
}

function NavLink({
  label,
  href,
  icon: Icon,
}: {
  label: string;
  href: string;
  icon: React.ElementType;
}) {
  return (
    <Link
      to={href}
      className={cn(
        'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-300 transition-colors hover:bg-white/10 hover:text-white',
        '[&.active]:bg-blue-600/20 [&.active]:text-blue-400',
      )}
    >
      <Icon className="h-4 w-4 shrink-0" />
      {label}
    </Link>
  );
}
