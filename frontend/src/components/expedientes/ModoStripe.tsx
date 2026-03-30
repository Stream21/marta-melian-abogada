import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { api } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ChevronDown, ChevronUp, FileText, Link2, Loader2 } from 'lucide-react';

interface ModoStripeProps {
  expedienteId: string;
}

export function ModoStripe({ expedienteId }: ModoStripeProps) {
  const [open, setOpen] = useState(false);
  const [amount, setAmount] = useState('');
  const [phone, setPhone] = useState('');

  const mutation = useMutation({
    mutationFn: () => api.postPaymentGenerateLink({ expedienteId, amount, phone }),
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!amount || !phone) return;
    mutation.mutate();
  };

  return (
    <div className="panel">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="flex w-full items-center justify-between px-5 py-4 text-left"
      >
        <div className="flex items-center gap-3">
          <div className="flex h-8 w-8 items-center justify-center rounded-md bg-violet-100">
            <Link2 className="h-4 w-4 text-violet-600" />
          </div>
          <div>
            <p className="text-sm font-semibold text-foreground">
              Modo 2 — Pasarela de Pago (Stripe)
            </p>
            <p className="text-xs text-muted-foreground">
              Genera un enlace de pago Stripe para enviar al cliente.
            </p>
          </div>
        </div>
        {open ? (
          <ChevronUp className="h-4 w-4 text-muted-foreground" />
        ) : (
          <ChevronDown className="h-4 w-4 text-muted-foreground" />
        )}
      </button>

      {open && (
        <div className="border-t px-5 pb-5 pt-4">
          <form onSubmit={handleSubmit} className="flex flex-wrap items-end gap-3">
            <div className="w-36">
              <Label
                htmlFor="stripe-amount"
                className="mb-1.5 block text-xs font-medium text-muted-foreground"
              >
                Importe (€)
              </Label>
              <Input
                id="stripe-amount"
                type="number"
                min="0"
                step="0.01"
                placeholder="0,00"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
              />
            </div>
            <div className="flex-1 min-w-48">
              <Label
                htmlFor="stripe-phone"
                className="mb-1.5 block text-xs font-medium text-muted-foreground"
              >
                Teléfono cliente (WhatsApp)
              </Label>
              <Input
                id="stripe-phone"
                type="tel"
                placeholder="+34 600 000 000"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
              />
            </div>
            <Button
              type="submit"
              disabled={mutation.isPending || !amount || !phone}
              className="bg-violet-600 hover:bg-violet-700 text-white"
            >
              {mutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Generar enlace de pago
            </Button>
          </form>

          {mutation.isSuccess && mutation.data?.url && (
            <div className="mt-4 flex items-center gap-3 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3">
              <FileText className="h-4 w-4 shrink-0 text-emerald-600" />
              <p className="flex-1 truncate text-sm text-emerald-800">
                Enlace generado:{' '}
                <a
                  href={mutation.data.url}
                  target="_blank"
                  rel="noreferrer"
                  className="font-medium underline hover:text-emerald-900"
                >
                  {mutation.data.url}
                </a>
              </p>
            </div>
          )}

          {mutation.isError && (
            <p className="mt-3 text-sm text-destructive">
              Error al generar el enlace. Inténtalo de nuevo.
            </p>
          )}
        </div>
      )}
    </div>
  );
}
