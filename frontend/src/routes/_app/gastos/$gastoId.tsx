import { createFileRoute } from '@tanstack/react-router';
import { GastoDetailPage } from '@/pages/GastoDetailPage';

export const Route = createFileRoute('/_app/gastos/$gastoId')({
  component: function RouteComponent() {
    const { gastoId } = Route.useParams();
    return <GastoDetailPage gastoId={gastoId} />;
  },
});
