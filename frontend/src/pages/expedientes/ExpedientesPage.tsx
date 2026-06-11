import { Link } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { FolderOpen, Plus } from 'lucide-react';

export function ExpedientesPage() {
  const { data: expedientes, isLoading } = useQuery({
    queryKey: ['expedientes'],
    queryFn: () => api.getExpedientes(),
  });

  return (
    <div className="p-6">
      <div className="mb-6 flex items-start justify-between gap-4">
        <div>
          <h1 className="page-title">Expedientes</h1>
          <p className="page-subtitle">Listado de expedientes activos</p>
        </div>
        <Button asChild>
          <Link to="/expedientes/nuevo">
            <Plus className="mr-2 h-4 w-4" />
            Nuevo expediente
          </Link>
        </Button>
      </div>

      {isLoading && (
        <div className="flex items-center justify-center py-12 text-muted-foreground">
          Cargando expedientes...
        </div>
      )}

      {!isLoading && (!expedientes || expedientes.length === 0) && (
        <div className="flex flex-col items-center justify-center py-16 text-muted-foreground">
          <FolderOpen className="mb-3 h-10 w-10" />
          <p className="text-sm">No hay expedientes</p>
        </div>
      )}

      {expedientes && expedientes.length > 0 && (
        <div className="panel">
          <table className="w-full text-sm">
            <thead className="border-b bg-muted/50">
              <tr>
                <th className="px-4 py-3 text-left section-label">Nº</th>
                <th className="px-4 py-3 text-left section-label">Título</th>
                <th className="px-4 py-3 text-left section-label">Cliente</th>
                <th className="px-4 py-3 text-left section-label">Fase</th>
                <th className="px-4 py-3 text-left section-label">Estado</th>
                <th className="px-4 py-3 text-left section-label">Fecha</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {expedientes.map((exp) => (
                <tr key={exp.id} className="hover:bg-primary/5 transition-colors">
                  <td className="px-4 py-3 font-mono text-muted-foreground">{exp.numero}</td>
                  <td className="px-4 py-3">
                    <Link
                      to="/expedientes/$expedienteId"
                      params={{ expedienteId: exp.id }}
                      className="font-medium text-primary hover:underline"
                    >
                      {exp.titulo}
                    </Link>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">{exp.clientName}</td>
                  <td className="px-4 py-3">
                    {exp.faseNegocio ? (
                      <Badge variant="info">{exp.faseNegocio}</Badge>
                    ) : (
                      <span className="text-muted-foreground">—</span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <Badge variant={exp.estado === 'abierto' ? 'default' : 'secondary'}>
                      {exp.estado}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
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
