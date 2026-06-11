import { createFileRoute } from '@tanstack/react-router';
import { ClienteDetailPage } from '@/pages/ClienteDetailPage';

export const Route = createFileRoute('/_app/clientes/$clienteId')({
  component: function RouteComponent() {
    const { clienteId } = Route.useParams();
    return <ClienteDetailPage clienteId={clienteId} />;
  },
});
