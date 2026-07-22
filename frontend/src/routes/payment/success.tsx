import { useEffect, useState } from 'react';
import { createFileRoute, Link } from '@tanstack/react-router';
import { AlertTriangle, CheckCircle2, Loader2 } from 'lucide-react';
import { api } from '@/api/client';
import { PortalClienteBrandingHero } from '@/components/cliente-portal/PortalClienteBrandingHero';
import { Button } from '@/components/ui/button';

export const Route = createFileRoute('/payment/success')({
  validateSearch: (search: Record<string, unknown>) => ({
    session_id: typeof search.session_id === 'string' ? search.session_id : undefined,
    token: typeof search.token === 'string' ? search.token : undefined,
  }),
  component: PaymentSuccessPage,
});

function PaymentSuccessPage() {
  const { session_id: sessionId, token } = Route.useSearch();
  const [estado, setEstado] = useState<'confirmando' | 'ok' | 'error'>('confirmando');
  const [mensajeError, setMensajeError] = useState<string | null>(null);

  useEffect(() => {
    if (!sessionId) {
      setEstado('ok');
      return;
    }

    let cancelado = false;

    void api
      .confirmarPagoStripeSesion(sessionId)
      .then(() => {
        if (!cancelado) setEstado('ok');
      })
      .catch((error: Error) => {
        if (!cancelado) {
          setEstado('error');
          setMensajeError(error.message);
        }
      });

    return () => {
      cancelado = true;
    };
  }, [sessionId]);

  // Tras confirmar, volver al portal del cliente automáticamente.
  useEffect(() => {
    if (!token || estado === 'confirmando') return;
    const t = window.setTimeout(() => {
      window.location.href = `/acceso/${encodeURIComponent(token)}`;
    }, estado === 'ok' ? 1800 : 4500);
    return () => window.clearTimeout(t);
  }, [token, estado]);

  return (
    <div className="min-h-screen bg-muted/30">
      <PortalClienteBrandingHero compact />
      <div className="flex items-center justify-center px-4 py-10">
        <div className="panel max-w-md w-full p-8 text-center">
          {estado === 'confirmando' ? (
            <>
              <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Loader2 className="h-8 w-8 animate-spin" />
              </div>
              <h1 className="text-xl font-semibold text-foreground">Confirmando pago…</h1>
              <p className="mt-2 text-sm text-muted-foreground">
                Estamos registrando su pago en el expediente. Un momento, por favor.
              </p>
            </>
          ) : estado === 'error' ? (
            <>
              <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                <AlertTriangle className="h-8 w-8" />
              </div>
              <h1 className="text-xl font-semibold text-foreground">Pago recibido en Stripe</h1>
              <p className="mt-2 text-sm text-muted-foreground">
                El cobro se ha procesado en la pasarela, pero no pudimos actualizar el expediente de
                inmediato.
              </p>
              {mensajeError && (
                <p className="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                  {mensajeError}
                </p>
              )}
              <p className="mt-3 text-xs text-muted-foreground">
                Su abogado verá el cobro en breve. Si el expediente no avanza, contacte con el bufete.
              </p>
            </>
          ) : (
            <>
              <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <CheckCircle2 className="h-8 w-8" />
              </div>
              <h1 className="text-xl font-semibold text-foreground">Pago recibido</h1>
              <p className="mt-2 text-sm text-muted-foreground">
                {token
                  ? 'Su pago se ha procesado correctamente. Le redirigimos de nuevo a su expediente…'
                  : 'Su pago se ha procesado correctamente. Puede cerrar esta ventana.'}
              </p>
            </>
          )}
          {sessionId && (
            <p className="mt-4 text-xs text-muted-foreground font-mono break-all">
              Referencia: {sessionId}
            </p>
          )}
          {token ? (
            <Button asChild className="mt-6 w-full">
              <Link to="/acceso/$token" params={{ token }}>
                Volver a mi expediente
              </Link>
            </Button>
          ) : (
            <p className="mt-6 text-xs text-muted-foreground">
              Puede cerrar esta ventana y volver al enlace que le envió su abogado.
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
