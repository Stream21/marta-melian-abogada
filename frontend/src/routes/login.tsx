import { createFileRoute, redirect } from '@tanstack/react-router';
import { LoginPage } from '@/pages/LoginPage';
import { isSessionActive } from '@/contexts/AuthContext';

export const Route = createFileRoute('/login')({
  beforeLoad: () => {
    if (isSessionActive()) {
      throw redirect({ to: '/expedientes' });
    }
  },
  component: LoginPage,
});
