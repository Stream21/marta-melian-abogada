import { createFileRoute } from '@tanstack/react-router';
import { ServiciosEditPage } from '@/pages/config/ServiciosEditPage';

export const Route = createFileRoute('/_app/config/servicios/$servicioId')({
  component: ServiciosEditRoute,
});

function ServiciosEditRoute() {
  const { servicioId } = Route.useParams();
  return <ServiciosEditPage servicioId={servicioId} />;
}
