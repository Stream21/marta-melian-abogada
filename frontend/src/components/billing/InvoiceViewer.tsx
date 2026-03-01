import { useState } from 'react';
import { CheckCircle, ExternalLink, MessageSquare, Loader2 } from 'lucide-react';
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
    <div className="rounded-xl border border-green-200 bg-green-50 p-6 shadow-sm">
      <div className="mb-4 flex items-center gap-2">
        <CheckCircle className="h-5 w-5 text-green-600" />
        <h3 className="font-semibold text-green-800">Factura Generada</h3>
      </div>

      <dl className="mb-5 space-y-2 text-sm">
        {invoice.numero && (
          <div className="flex justify-between">
            <dt className="text-slate-500">Número</dt>
            <dd className="font-mono font-medium text-slate-800">{invoice.numero}</dd>
          </div>
        )}
        {invoice.importe !== undefined && (
          <div className="flex justify-between">
            <dt className="text-slate-500">Importe</dt>
            <dd className="font-medium text-slate-800">
              {Number(invoice.importe).toLocaleString('es-ES', {
                style: 'currency',
                currency: 'EUR',
              })}
            </dd>
          </div>
        )}
        {invoice.holdedId && (
          <div className="flex justify-between">
            <dt className="text-slate-500">ID Holded</dt>
            <dd className="font-mono text-xs text-slate-500">{invoice.holdedId}</dd>
          </div>
        )}
      </dl>

      <div className="flex flex-col gap-2">
        {pdfUrl && (
          <a
            href={pdfUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
          >
            <ExternalLink className="h-4 w-4" />
            Ver Factura PDF
          </a>
        )}

        {invoice.invoiceId && (
          <button
            onClick={handleWhatsapp}
            disabled={sending}
            className="flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:opacity-60"
          >
            {sending ? (
              <Loader2 className="h-4 w-4 animate-spin" />
            ) : (
              <MessageSquare className="h-4 w-4" />
            )}
            {sending ? 'Enviando…' : 'Simular Envío WhatsApp'}
          </button>
        )}
      </div>

      {whatsappLog && (
        <div className="mt-4 rounded-lg border border-green-300 bg-white px-4 py-3 font-mono text-xs text-green-700">
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
