import { createFileRoute } from '@tanstack/react-router';
import { ClienteNuevoPage } from '@/pages/ClienteNuevoPage';

export const Route = createFileRoute('/_app/clientes/nuevo')({
  component: ClienteNuevoPage,
});
