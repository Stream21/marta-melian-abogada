import { createFileRoute, redirect } from '@tanstack/react-router';

export const Route = createFileRoute('/_app/config/')({
  beforeLoad: () => {
    throw redirect({ to: '/config/tipos-caso' });
  },
  component: () => null,
});
