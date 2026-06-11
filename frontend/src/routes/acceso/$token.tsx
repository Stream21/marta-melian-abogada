import { createFileRoute } from '@tanstack/react-router';
import { ClienteContratacionPortalPage } from '@/pages/ClienteContratacionPortalPage';

export const Route = createFileRoute('/acceso/$token')({
  component: AccesoRoute,
});

function AccesoRoute() {
  const { token } = Route.useParams();
  return <ClienteContratacionPortalPage token={token} />;
}
