import { createFileRoute } from '@tanstack/react-router';
import { TramitesFasesPage } from '@/pages/config/TramitesFasesPage';

export const Route = createFileRoute('/_app/config/tramites/$tramiteId/fases')({
  component: TramitesFasesRoute,
});

function TramitesFasesRoute() {
  const { tramiteId } = Route.useParams();
  return <TramitesFasesPage tramiteId={tramiteId} />;
}
