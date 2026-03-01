import { createFileRoute } from '@tanstack/react-router';
import { ExpedienteDetailPage } from '@/pages/expedientes/ExpedienteDetailPage';

export const Route = createFileRoute('/_app/expedientes/$expedienteId')({
  component: function RouteComponent() {
    const { expedienteId } = Route.useParams();
    return <ExpedienteDetailPage expedienteId={expedienteId} />;
  },
});
