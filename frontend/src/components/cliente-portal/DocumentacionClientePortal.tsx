import type { AccesoExpedienteResponse } from '@/api/client';
import { DocumentacionUploadPanel } from '@/components/cliente-portal/DocumentacionUploadPanel';

interface DocumentacionClientePortalProps {
  token: string;
  data: AccesoExpedienteResponse;
}

export function DocumentacionClientePortal({ token, data }: DocumentacionClientePortalProps) {
  const documentacion = data.documentacion;

  if (!documentacion) {
    return (
      <div className="py-8 text-center text-sm text-muted-foreground">
        Cargando documentación requerida…
      </div>
    );
  }

  return (
    <div className="space-y-5">
      <h2 className="text-xl font-semibold tracking-tight text-foreground">
        Documentación requerida
      </h2>

      <DocumentacionUploadPanel token={token} documentos={documentacion.documentos} />
    </div>
  );
}
