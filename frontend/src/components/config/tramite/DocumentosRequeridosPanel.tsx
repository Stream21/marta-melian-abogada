import { DocumentosRequeridosEditor } from '@/components/config/DocumentosRequeridosEditor';

interface DocumentosRequeridosPanelProps {
  tramiteId: string;
}

export function DocumentosRequeridosPanel({ tramiteId }: DocumentosRequeridosPanelProps) {
  return (
    <DocumentosRequeridosEditor
      scope="tramite"
      entityId={tramiteId}
      title="Documentación específica del trámite"
      subtitle="Documentos adicionales de este trámite, además de los heredados del servicio."
    />
  );
}
