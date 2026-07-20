import { createFileRoute } from '@tanstack/react-router';
import { ExpedienteDetailPage } from '@/pages/expedientes/ExpedienteDetailPage';
import type { ExpedienteNotificacionSearch } from '@/lib/notificacion-destino';

export const Route = createFileRoute('/_app/expedientes/$expedienteId')({
  validateSearch: (search: Record<string, unknown>): ExpedienteNotificacionSearch => ({
    tab: typeof search.tab === 'string' ? search.tab : undefined,
    hito: typeof search.hito === 'string' ? search.hito : undefined,
    paso: typeof search.paso === 'string' ? search.paso : undefined,
    documento: typeof search.documento === 'string' ? search.documento : undefined,
    revision: typeof search.revision === 'string' ? search.revision : undefined,
  }),
  component: function RouteComponent() {
    const { expedienteId } = Route.useParams();
    const search = Route.useSearch();
    return <ExpedienteDetailPage expedienteId={expedienteId} notificacionSearch={search} />;
  },
});
