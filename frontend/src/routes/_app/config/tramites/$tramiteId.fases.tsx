import { createFileRoute, Navigate } from '@tanstack/react-router';

export const Route = createFileRoute('/_app/config/tramites/$tramiteId/fases')({
  component: RedirectFasesToConfiguracion,
});

function RedirectFasesToConfiguracion() {
  const { tramiteId } = Route.useParams();
  return (
    <Navigate
      to="/config/tramites/$tramiteId/configuracion"
      params={{ tramiteId }}
      search={{ tab: 'hoja-encargo' }}
    />
  );
}
