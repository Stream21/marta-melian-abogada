import { Link } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { FolderOpen } from 'lucide-react';

export function ExpedientesPage() {
  const { data: expedientes, isLoading } = useQuery({
    queryKey: ['expedientes'],
    queryFn: () => api.getExpedientes(),
  });

  return (
    <div className="p-6">
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-slate-800">Expedientes</h1>
        <p className="mt-1 text-sm text-slate-500">Listado de expedientes activos</p>
      </div>

      {isLoading && (
        <div className="flex items-center justify-center py-12 text-slate-400">
          Cargando expedientes...
        </div>
      )}

      {!isLoading && (!expedientes || expedientes.length === 0) && (
        <div className="flex flex-col items-center justify-center py-16 text-slate-400">
          <FolderOpen className="mb-3 h-10 w-10" />
          <p className="text-sm">No hay expedientes</p>
        </div>
      )}

      {expedientes && expedientes.length > 0 && (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
          <table className="w-full text-sm">
            <thead className="border-b border-slate-100 bg-slate-50">
              <tr>
                <th className="px-4 py-3 text-left font-medium text-slate-500">Nº</th>
                <th className="px-4 py-3 text-left font-medium text-slate-500">Título</th>
                <th className="px-4 py-3 text-left font-medium text-slate-500">Cliente</th>
                <th className="px-4 py-3 text-left font-medium text-slate-500">Estado</th>
                <th className="px-4 py-3 text-left font-medium text-slate-500">Fecha</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {expedientes.map((exp) => (
                <tr key={exp.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3 font-mono text-slate-600">{exp.numero}</td>
                  <td className="px-4 py-3">
                    <Link
                      to="/expedientes/$expedienteId"
                      params={{ expedienteId: exp.id }}
                      className="font-medium text-blue-600 hover:underline"
                    >
                      {exp.titulo}
                    </Link>
                  </td>
                  <td className="px-4 py-3 text-slate-600">{exp.clientName}</td>
                  <td className="px-4 py-3">
                    <Badge variant={exp.estado === 'activo' ? 'default' : 'secondary'}>
                      {exp.estado}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-slate-500">
                    {new Date(exp.fechaApertura).toLocaleDateString('es-ES')}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
