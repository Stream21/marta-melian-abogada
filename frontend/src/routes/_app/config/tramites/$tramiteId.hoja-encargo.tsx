import { createFileRoute, Navigate } from '@tanstack/react-router';

export const Route = createFileRoute('/_app/config/tramites/$tramiteId/hoja-encargo')({
  component: RedirectToConfiguracion,
});

function RedirectToConfiguracion() {
  const { tramiteId } = Route.useParams();
  return (
    <Navigate
      to="/config/tramites/$tramiteId/configuracion"
      params={{ tramiteId }}
      search={{ tab: 'hoja-encargo' }}
    />
  );
}
