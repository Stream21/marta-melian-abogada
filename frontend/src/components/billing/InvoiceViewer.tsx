import { useState } from 'react';
import { CheckCircle, ExternalLink, MessageSquare, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { api, type QuickInvoiceResponse } from '../../api/client';

interface InvoiceViewerProps {
  invoice: QuickInvoiceResponse;
}

const API_BASE = import.meta.env.VITE_API_BASE_URL || '';

export function InvoiceViewer({ invoice }: InvoiceViewerProps) {
  const [sending, setSending] = useState(false);
  const [whatsappLog, setWhatsappLog] = useState<string | null>(null);
  const [whatsappError, setWhatsappError] = useState<string | null>(null);

  const pdfUrl = invoice.pdfUrl
    ? invoice.pdfUrl.startsWith('http')
      ? invoice.pdfUrl
      : API_BASE + invoice.pdfUrl
    : null;

  const handleWhatsapp = async () => {
    if (!invoice.invoiceId) return;
    setSending(true);
    setWhatsappLog(null);
    setWhatsappError(null);
    try {
      const res = await api.postInvoiceWhatsapp(invoice.invoiceId);
      if (res.success) {
        setWhatsappLog(res.message || 'Simulación enviada correctamente.');
      } else {
        setWhatsappError('No se pudo enviar la simulación.');
      }
    } catch (err) {
      setWhatsappError(err instanceof Error ? err.message : 'Error al simular el envío.');
    } finally {
      setSending(false);
    }
  };

  return (
    <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
      <div className="mb-4 flex items-center gap-2">
        <CheckCircle className="h-5 w-5 text-emerald-600" />
        <h3 className="font-semibold text-emerald-800">Factura Generada</h3>
      </div>

      <dl className="mb-5 space-y-2 text-sm">
        {invoice.numero && (
          <div className="flex justify-between">
            <dt className="text-muted-foreground">Número</dt>
            <dd className="font-mono font-medium text-foreground">{invoice.numero}</dd>
          </div>
        )}
        {invoice.importe !== undefined && (
          <div className="flex justify-between">
            <dt className="text-muted-foreground">Importe</dt>
            <dd className="font-medium text-foreground">
              {Number(invoice.importe).toLocaleString('es-ES', {
                style: 'currency',
                currency: 'EUR',
              })}
            </dd>
          </div>
        )}
        {invoice.holdedId && (
          <div className="flex justify-between">
            <dt className="text-muted-foreground">ID Holded</dt>
            <dd className="font-mono text-xs text-muted-foreground">{invoice.holdedId}</dd>
          </div>
        )}
      </dl>

      <div className="flex flex-col gap-2">
        {pdfUrl && (
          <Button variant="outline" className="w-full" asChild>
            <a href={pdfUrl} target="_blank" rel="noopener noreferrer">
              <ExternalLink className="mr-2 h-4 w-4" />
              Ver Factura PDF
            </a>
          </Button>
        )}

        {invoice.invoiceId && (
          <Button
            onClick={handleWhatsapp}
            disabled={sending}
            className="w-full bg-emerald-600 hover:bg-emerald-700 text-white"
          >
            {sending ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <MessageSquare className="mr-2 h-4 w-4" />
            )}
            {sending ? 'Enviando…' : 'Simular Envío WhatsApp'}
          </Button>
        )}
      </div>

      {whatsappLog && (
        <div className="mt-4 rounded-lg border border-emerald-300 bg-white px-4 py-3 font-mono text-xs text-emerald-700">
          {whatsappLog}
        </div>
      )}

      {whatsappError && (
        <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {whatsappError}
        </div>
      )}
    </div>
  );
}
