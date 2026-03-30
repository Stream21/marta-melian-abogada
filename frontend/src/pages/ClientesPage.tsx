import { useState } from 'react';
import { Users } from 'lucide-react';
import { ClientList } from '@/components/billing/ClientList';
import { InvoiceGenerator } from '@/components/billing/InvoiceGenerator';
import { InvoiceViewer } from '@/components/billing/InvoiceViewer';
import type { MockContact, QuickInvoiceResponse } from '@/api/client';

export function ClientesPage() {
  const [selectedContact, setSelectedContact] = useState<MockContact | null>(null);
  const [lastInvoice, setLastInvoice] = useState<QuickInvoiceResponse | null>(null);

  const handleSelectContact = (contact: MockContact) => {
    setSelectedContact(contact);
    setLastInvoice(null);
  };

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div className="mb-8 flex items-center gap-3">
        <Users className="h-7 w-7 text-indigo-600" />
        <div>
          <h1 className="page-title">Clientes</h1>
          <p className="page-subtitle">
            Selecciona un cliente para emitir una factura directa.
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <ClientList
            selectedContactId={selectedContact?.id ?? null}
            onSelectContact={handleSelectContact}
          />
        </div>

        <div className="space-y-6">
          {selectedContact ? (
            <>
              <InvoiceGenerator
                contact={selectedContact}
                onInvoiceCreated={(invoice) => setLastInvoice(invoice)}
              />
              {lastInvoice && <InvoiceViewer invoice={lastInvoice} />}
            </>
          ) : (
            <div className="flex flex-col items-center justify-center rounded-xl border border-dashed py-16 text-center text-muted-foreground">
              <Users className="mb-3 h-8 w-8" />
              <p className="text-sm">Selecciona un cliente de la lista para generar una factura.</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
