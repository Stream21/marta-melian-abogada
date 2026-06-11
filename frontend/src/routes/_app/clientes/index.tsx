import { createFileRoute } from '@tanstack/react-router';
import { ClientesPage } from '@/pages/ClientesPage';

export const Route = createFileRoute('/_app/clientes/')({
  component: ClientesPage,
});
