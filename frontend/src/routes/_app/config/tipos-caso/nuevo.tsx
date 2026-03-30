import { createFileRoute } from '@tanstack/react-router';
import { TiposCasoNuevoPage } from '@/pages/config/TiposCasoNuevoPage';

export const Route = createFileRoute('/_app/config/tipos-caso/nuevo')({
  component: TiposCasoNuevoPage,
});
