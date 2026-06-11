import { createFileRoute } from '@tanstack/react-router';
import { NuevoExpedientePage } from '@/pages/expedientes/NuevoExpedientePage';

export const Route = createFileRoute('/_app/expedientes/nuevo')({
  component: NuevoExpedientePage,
});
