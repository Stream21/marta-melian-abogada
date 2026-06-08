import { createFileRoute } from '@tanstack/react-router';
import { TramitesEditPage } from '@/pages/config/TramitesEditPage';

export const Route = createFileRoute('/_app/config/tramites/$tramiteId/')({
  component: TramitesEditRoute,
});

function TramitesEditRoute() {
  const { tramiteId } = Route.useParams();
  return <TramitesEditPage tramiteId={tramiteId} />;
}
