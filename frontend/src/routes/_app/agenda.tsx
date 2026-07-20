import { createFileRoute, redirect } from '@tanstack/react-router';

export const Route = createFileRoute('/_app/agenda')({
  beforeLoad: () => {
    throw redirect({ to: '/expedientes' });
  },
  component: () => null,
});
