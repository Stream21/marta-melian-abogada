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
      <div className="flex items-center justify-center py-12 text-slate-400">
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
      <div className="flex flex-col items-center justify-center py-12 text-slate-400">
        <UserCheck className="mb-2 h-8 w-8" />
        <p className="text-sm">No hay clientes registrados en Holded.</p>
      </div>
    );
  }

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-slate-100 bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
            <th className="px-4 py-3">Nombre</th>
            <th className="px-4 py-3">Email</th>
            <th className="px-4 py-3">NIF / Código</th>
            <th className="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {contacts.map((contact) => {
            const isSelected = contact.id === selectedContactId;
            return (
              <tr
                key={contact.id}
                className={`transition-colors ${isSelected ? 'bg-indigo-50' : 'hover:bg-slate-50'}`}
              >
                <td className="px-4 py-3 font-medium text-slate-800">{contact.name}</td>
                <td className="px-4 py-3 text-slate-600">{contact.email}</td>
                <td className="px-4 py-3 font-mono text-slate-500">{contact.code || '—'}</td>
                <td className="px-4 py-3 text-right">
                  <button
                    onClick={() => onSelectContact(contact)}
                    className={`rounded-lg px-3 py-1.5 text-xs font-medium transition-colors ${
                      isSelected
                        ? 'bg-indigo-600 text-white'
                        : 'border border-indigo-200 text-indigo-600 hover:bg-indigo-50'
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
