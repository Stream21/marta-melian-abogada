import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { createRouter, RouterProvider } from '@tanstack/react-router';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import './index.css';

import { routeTree } from './routeTree.gen';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import type { AuthContextValue } from './contexts/AuthContext';

const router = createRouter({
  routeTree,
  context: {
    auth: undefined! as AuthContextValue,
  },
});

declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router;
  }
}

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

function InnerApp() {
  const auth = useAuth();
  router.update({ context: { auth } });
  return <RouterProvider router={router} />;
}

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <InnerApp />
      </AuthProvider>
    </QueryClientProvider>
  </StrictMode>,
);
