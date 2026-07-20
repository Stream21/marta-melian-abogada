import { createFileRoute, Link } from '@tanstack/react-router';
import { XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';

export const Route = createFileRoute('/payment/cancel')({
  component: PaymentCancelPage,
});

function PaymentCancelPage() {
  return (
    <div className="min-h-screen bg-muted/30 flex items-center justify-center px-4">
      <div className="panel max-w-md w-full p-8 text-center">
        <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-700">
          <XCircle className="h-8 w-8" />
        </div>
        <h1 className="text-xl font-semibold text-foreground">Pago cancelado</h1>
        <p className="mt-2 text-sm text-muted-foreground">
          No se ha realizado ningún cobro. Puede volver al portal del cliente e intentarlo de nuevo
          cuando lo desee.
        </p>
        <Button asChild className="mt-6 w-full" variant="outline">
          <Link to="/">Volver al inicio</Link>
        </Button>
      </div>
    </div>
  );
}
