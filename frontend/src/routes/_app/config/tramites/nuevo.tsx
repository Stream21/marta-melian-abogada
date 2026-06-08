import { createFileRoute } from '@tanstack/react-router';
import { TramitesNuevoPage } from '@/pages/config/TramitesNuevoPage';

export const Route = createFileRoute('/_app/config/tramites/nuevo')({
  component: TramitesNuevoPage,
});
