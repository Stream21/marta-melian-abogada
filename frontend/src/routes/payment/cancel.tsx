import { createFileRoute, Link } from '@tanstack/react-router';
import { XCircle } from 'lucide-react';
import { PortalClienteBrandingHero } from '@/components/cliente-portal/PortalClienteBrandingHero';
import { Button } from '@/components/ui/button';

export const Route = createFileRoute('/payment/cancel')({
  validateSearch: (search: Record<string, unknown>) => ({
    token: typeof search.token === 'string' ? search.token : undefined,
  }),
  component: PaymentCancelPage,
});

function PaymentCancelPage() {
  const { token } = Route.useSearch();

  return (
    <div className="min-h-screen bg-muted/30">
      <PortalClienteBrandingHero compact />
      <div className="flex items-center justify-center px-4 py-10">
        <div className="panel max-w-md w-full p-8 text-center">
          <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-700">
            <XCircle className="h-8 w-8" />
          </div>
          <h1 className="text-xl font-semibold text-foreground">Pago cancelado</h1>
          <p className="mt-2 text-sm text-muted-foreground">
            No se ha realizado ningún cobro. Puede volver a su expediente e intentarlo de nuevo
            cuando lo desee.
          </p>
          {token ? (
            <Button asChild className="mt-6 w-full">
              <Link to="/acceso/$token" params={{ token }}>
                Volver a mi expediente
              </Link>
            </Button>
          ) : (
            <p className="mt-6 text-xs text-muted-foreground">
              Use el enlace que le envió su abogado para continuar.
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
