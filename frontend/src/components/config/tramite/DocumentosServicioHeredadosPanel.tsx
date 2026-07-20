import { useQuery } from '@tanstack/react-query';
import { api } from '@/api/client';
import { DocumentosRequeridosEditor } from '@/components/config/DocumentosRequeridosEditor';

interface DocumentosServicioHeredadosPanelProps {
  servicioId: string;
}

export function DocumentosServicioHeredadosPanel({ servicioId }: DocumentosServicioHeredadosPanelProps) {
  const { data } = useQuery({
    queryKey: ['documentos-requeridos-servicio', servicioId],
    queryFn: () => api.getDocumentosRequeridosServicio(servicioId),
  });

  if (!data?.documentos.length) {
    return null;
  }

  return (
    <div className="mb-6">
      <DocumentosRequeridosEditor
        scope="servicio"
        entityId={servicioId}
        readOnly
        title="Documentos heredados del servicio"
        subtitle="Configurados a nivel de servicio. Se incluyen automáticamente en todos los trámites."
      />
    </div>
  );
}
