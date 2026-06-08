import { createFileRoute, Outlet } from '@tanstack/react-router';

export const Route = createFileRoute('/_app/config/tramites/$tramiteId')({
  component: TramiteIdLayout,
});

function TramiteIdLayout() {
  return <Outlet />;
}
