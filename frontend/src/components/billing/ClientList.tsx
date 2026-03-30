import { useQuery } from '@tanstack/react-query';
import { UserCheck, Loader2, AlertCircle } from 'lucide-react';
import { api, type MockContact } from '../../api/client';

interface ClientListProps {
  selectedContactId: string | null;
  onSelectContact: (contact: MockContact) => void;
}

export function ClientList({ selectedContactId, onSelectContact }: ClientListProps) {
  const {
    data: contacts = [],
    isLoading,
    error,
  } = useQuery({
    queryKey: ['contacts'],
    queryFn: api.getContacts,
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12 text-muted-foreground">
        <Loader2 className="mr-2 h-5 w-5 animate-spin" />
        <span className="text-sm">Cargando clientes…</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <AlertCircle className="h-4 w-4 shrink-0" />
        No se pudo cargar la lista de clientes.
      </div>
    );
  }

  if (contacts.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
        <UserCheck className="mb-2 h-8 w-8" />
        <p className="text-sm">No hay clientes registrados en Holded.</p>
      </div>
    );
  }

  return (
    <div className="panel">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b bg-muted/50 text-left">
            <th className="px-4 py-3 section-label">Nombre</th>
            <th className="px-4 py-3 section-label">Email</th>
            <th className="px-4 py-3 section-label">NIF / Código</th>
            <th className="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody className="divide-y divide-border">
          {contacts.map((contact) => {
            const isSelected = contact.id === selectedContactId;
            return (
              <tr
                key={contact.id}
                className={`transition-colors ${isSelected ? 'bg-primary/5' : 'hover:bg-muted/50'}`}
              >
                <td className="px-4 py-3 font-medium text-foreground">{contact.name}</td>
                <td className="px-4 py-3 text-muted-foreground">{contact.email}</td>
                <td className="px-4 py-3 font-mono text-muted-foreground">{contact.code || '—'}</td>
                <td className="px-4 py-3 text-right">
                  <button
                    onClick={() => onSelectContact(contact)}
                    className={`rounded-lg px-3 py-1.5 text-xs font-medium transition-colors ${
                      isSelected
                        ? 'bg-primary text-primary-foreground'
                        : 'border border-primary/20 text-primary hover:bg-primary/5'
                    }`}
                  >
                    {isSelected ? 'Seleccionado' : 'Emitir Factura'}
                  </button>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}
