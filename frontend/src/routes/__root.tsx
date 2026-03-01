import { createRootRouteWithContext, Outlet } from '@tanstack/react-router';
import type { AuthContextValue } from '@/contexts/AuthContext';

interface RouterContext {
  auth: AuthContextValue;
}

export const Route = createRootRouteWithContext<RouterContext>()({
  component: () => <Outlet />,
});
