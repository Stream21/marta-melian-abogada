import { createFileRoute } from '@tanstack/react-router';
import { TiposCasoEditPage } from '@/pages/config/TiposCasoEditPage';

export const Route = createFileRoute('/_app/config/tipos-caso/$tipoCasoId')({
  component: function RouteComponent() {
    const { tipoCasoId } = Route.useParams();
    return <TiposCasoEditPage tipoCasoId={tipoCasoId} />;
  },
});
