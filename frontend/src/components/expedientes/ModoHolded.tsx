import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api, type InvoiceResponse } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Loader2 } from 'lucide-react';
import { InvoiceTable } from './InvoiceTable';

interface ModoHoldedProps {
  expedienteId: string;
  invoices: InvoiceResponse[];
}

export function ModoHolded({ expedienteId, invoices }: ModoHoldedProps) {
  const queryClient = useQueryClient();
  const [concepto, setConcepto] = useState('');
  const [cantidad, setCantidad] = useState('');

  const mutation = useMutation({
    mutationFn: () => api.postInvoiceHolded({ expedienteId, concepto, importe: cantidad }),
    onSuccess: () => {
      setConcepto('');
      setCantidad('');
      void queryClient.invalidateQueries({ queryKey: ['invoices', expedienteId] });
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!concepto || !cantidad) return;
    mutation.mutate();
  };

  return (
    <div className="space-y-4">
      <div>
        <h3 className="text-base font-semibold text-foreground">Modo 1 — Cobro Manual (Holded)</h3>
        <p className="mt-0.5 text-sm text-muted-foreground">
          Genera facturas directamente en Holded y lleva el seguimiento aquí.
        </p>
      </div>

      <form onSubmit={handleSubmit} className="flex flex-wrap items-end gap-3">
        <div className="flex-1 min-w-48">
          <Label htmlFor="concepto" className="mb-1.5 block text-xs font-medium text-muted-foreground">
            Concepto de Factura
          </Label>
          <Input
            id="concepto"
            placeholder="Ej: Honorarios — Fase 1"
            value={concepto}
            onChange={(e) => setConcepto(e.target.value)}
          />
        </div>
        <div className="w-36">
          <Label htmlFor="cantidad" className="mb-1.5 block text-xs font-medium text-muted-foreground">
            Cantidad (€)
          </Label>
          <Input
            id="cantidad"
            type="number"
            min="0"
            step="0.01"
            placeholder="0,00"
            value={cantidad}
            onChange={(e) => setCantidad(e.target.value)}
          />
        </div>
        <Button
          type="submit"
          disabled={mutation.isPending || !concepto || !cantidad}
          className="bg-orange-500 hover:bg-orange-600 text-white"
        >
          {mutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          Generar Factura en Holded
        </Button>
      </form>

      {mutation.isError && (
        <p className="text-sm text-destructive">Error al generar la factura. Inténtalo de nuevo.</p>
      )}

      <InvoiceTable invoices={invoices} expedienteId={expedienteId} />
    </div>
  );
}
