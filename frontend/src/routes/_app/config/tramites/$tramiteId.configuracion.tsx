import { createFileRoute } from '@tanstack/react-router';
import {
  TramiteConfiguracionPage,
  type TramiteConfigTab,
} from '@/pages/config/TramiteConfiguracionPage';

type ConfiguracionSearch = {
  tab?: TramiteConfigTab;
};

export const Route = createFileRoute('/_app/config/tramites/$tramiteId/configuracion')({
  validateSearch: (search: Record<string, unknown>): ConfiguracionSearch => {
    const tab = search.tab;
    const validTabs: TramiteConfigTab[] = [
      'hoja-encargo',
      'designacion',
      'rgpd',
      'documentacion',
    ];
    return {
      tab: typeof tab === 'string' && validTabs.includes(tab as TramiteConfigTab)
        ? (tab as TramiteConfigTab)
        : 'hoja-encargo',
    };
  },
  component: TramiteConfiguracionRoute,
});

function TramiteConfiguracionRoute() {
  const { tramiteId } = Route.useParams();
  const { tab = 'hoja-encargo' } = Route.useSearch();
  return <TramiteConfiguracionPage tramiteId={tramiteId} tab={tab} />;
}
