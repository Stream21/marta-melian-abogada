import { useState } from 'react';
import { createFileRoute, Outlet, redirect } from '@tanstack/react-router';
import { Sidebar } from '@/components/layout/Sidebar';
import { Topbar } from '@/components/layout/Topbar';
import { isSessionActive } from '@/contexts/AuthContext';

export const Route = createFileRoute('/_app')({
  beforeLoad: () => {
    if (!isSessionActive()) {
      throw redirect({ to: '/login' });
    }
  },
  component: AppLayout,
});

function AppLayout() {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  return (
    <div className="flex h-screen overflow-hidden bg-gray-50">
      {mobileMenuOpen && (
        <div
          className="fixed inset-0 z-20 bg-black/50 lg:hidden"
          onClick={() => setMobileMenuOpen(false)}
        />
      )}
      <Sidebar
        collapsed={sidebarCollapsed}
        mobileOpen={mobileMenuOpen}
        onToggle={() => setSidebarCollapsed((p) => !p)}
        onMobileClose={() => setMobileMenuOpen(false)}
      />
      <div className="flex flex-1 flex-col overflow-hidden min-w-0">
        <Topbar onMobileMenuToggle={() => setMobileMenuOpen((p) => !p)} />
        <main className="flex-1 overflow-y-auto">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
