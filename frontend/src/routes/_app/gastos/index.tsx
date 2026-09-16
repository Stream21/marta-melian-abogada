import { createFileRoute } from '@tanstack/react-router';
import { GastosPage } from '@/pages/GastosPage';

export const Route = createFileRoute('/_app/gastos/')({
  component: GastosPage,
});
