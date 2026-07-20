import { createFileRoute } from '@tanstack/react-router';
import { FacturacionGlobalPage } from '@/pages/FacturacionGlobalPage';

export const Route = createFileRoute('/_app/facturacion')({
  component: FacturacionGlobalPage,
});
