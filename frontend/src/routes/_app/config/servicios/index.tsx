import { createFileRoute } from '@tanstack/react-router';
import { ServiciosPage } from '@/pages/config/ServiciosPage';

export const Route = createFileRoute('/_app/config/servicios/')({
  component: ServiciosPage,
});
