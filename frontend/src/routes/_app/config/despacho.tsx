import { createFileRoute } from '@tanstack/react-router';
import { DespachoConfigPage } from '@/pages/config/DespachoConfigPage';

export const Route = createFileRoute('/_app/config/despacho')({
  component: DespachoConfigRoute,
});

function DespachoConfigRoute() {
  return <DespachoConfigPage />;
}
