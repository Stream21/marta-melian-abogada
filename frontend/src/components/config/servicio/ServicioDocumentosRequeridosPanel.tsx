import { DocumentosRequeridosEditor } from '@/components/config/DocumentosRequeridosEditor';

interface ServicioDocumentosRequeridosPanelProps {
  servicioId: string;
}

export function ServicioDocumentosRequeridosPanel({ servicioId }: ServicioDocumentosRequeridosPanelProps) {
  return (
    <DocumentosRequeridosEditor
      scope="servicio"
      entityId={servicioId}
      title="Documentación común del servicio"
      subtitle="Estos documentos se aplican a todos los trámites de este servicio en la fase de requerimientos."
    />
  );
}
