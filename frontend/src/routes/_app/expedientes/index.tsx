import { createFileRoute } from '@tanstack/react-router';
import { ExpedientesPage } from '@/pages/expedientes/ExpedientesPage';

export const Route = createFileRoute('/_app/expedientes/')({
  component: ExpedientesPage,
});
