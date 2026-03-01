import { useState } from 'react';
import { FileText, Loader2 } from 'lucide-react';
import { api, type MockContact, type QuickInvoiceResponse } from '../../api/client';

interface InvoiceGeneratorProps {
  contact: MockContact;
  onInvoiceCreated: (invoice: QuickInvoiceResponse) => void;
}

export function InvoiceGenerator({ contact, onInvoiceCreated }: InvoiceGeneratorProps) {
  const [concepto, setConcepto] = useState('');
  const [importe, setImporte] = useState('');
  const [telefono, setTelefono] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      const res = await api.postQuickInvoice({
        contactId: contact.id,
        concepto,
        importe: parseFloat(importe),
        telefono: telefono || undefined,
      });
      if (res.success) {
        onInvoiceCreated(res);
        setConcepto('');
        setImporte('');
        setTelefono('');
      } else {
        setError(res.error || 'Error al generar la factura.');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al generar la factura.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <div className="mb-5 flex items-center gap-2">
        <FileText className="h-5 w-5 text-indigo-600" />
        <h3 className="font-semibold text-slate-800">Nueva Factura</h3>
      </div>

      <div className="mb-4 rounded-lg bg-slate-50 px-4 py-2 text-sm text-slate-600">
        Cliente: <span className="font-medium text-slate-800">{contact.name}</span>
        {contact.email && <span className="ml-2 text-slate-400">({contact.email})</span>}
      </div>

      {error && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Concepto *</label>
          <input
            type="text"
            required
            value={concepto}
            onChange={(e) => setConcepto(e.target.value)}
            placeholder="Ej. Honorarios consulta jurídica"
            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
          />
        </div>

        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Importe (€) *</label>
          <input
            type="number"
            required
            min="0.01"
            step="0.01"
            value={importe}
            onChange={(e) => setImporte(e.target.value)}
            placeholder="0.00"
            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
          />
        </div>

        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Teléfono WhatsApp</label>
          <input
            type="tel"
            value={telefono}
            onChange={(e) => setTelefono(e.target.value)}
            placeholder="+34 600 000 000"
            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
          />
        </div>

        <button
          type="submit"
          disabled={submitting}
          className="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:opacity-60"
        >
          {submitting && <Loader2 className="h-4 w-4 animate-spin" />}
          {submitting ? 'Generando…' : 'Generar Factura'}
        </button>
      </form>
    </div>
  );
}
