import { createFileRoute } from '@tanstack/react-router';
import { TramitesPage } from '@/pages/config/TramitesPage';

export const Route = createFileRoute('/_app/config/tramites/')({
  component: TramitesPage,
});
