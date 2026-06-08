import { createFileRoute } from '@tanstack/react-router';
import { ServiciosNuevoPage } from '@/pages/config/ServiciosNuevoPage';

export const Route = createFileRoute('/_app/config/servicios/nuevo')({
  component: ServiciosNuevoPage,
});
