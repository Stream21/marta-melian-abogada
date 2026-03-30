import { createFileRoute } from '@tanstack/react-router';
import { TiposCasoPage } from '@/pages/config/TiposCasoPage';

export const Route = createFileRoute('/_app/config/tipos-caso/')({
  component: TiposCasoPage,
});
