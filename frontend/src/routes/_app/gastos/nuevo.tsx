import { createFileRoute } from '@tanstack/react-router';
import { GastoNuevoPage } from '@/pages/GastoNuevoPage';

export const Route = createFileRoute('/_app/gastos/nuevo')({
  component: GastoNuevoPage,
});
