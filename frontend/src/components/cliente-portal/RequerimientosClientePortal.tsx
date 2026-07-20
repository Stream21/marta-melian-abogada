import { Clock, FileText, AlertTriangle } from 'lucide-react';
import type { AccesoExpedienteResponse } from '@/api/client';
import { RequerimientosUploadPanel } from '@/components/cliente-portal/RequerimientosUploadPanel';

interface RequerimientosClientePortalProps {
  token: string;
  data: AccesoExpedienteResponse;
}

export function RequerimientosClientePortal({ token, data }: RequerimientosClientePortalProps) {
  const req = data.requerimientos;

  if (!req) {
    return (
      <div className="py-8 text-center text-sm text-muted-foreground">
        Cargando documentación requerida…
      </div>
    );
  }

  return (
    <div>
      <div className="mb-5 flex items-center gap-3 border-b border-border pb-4">
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
          <FileText className="h-5 w-5" />
        </div>
        <div>
          <h2 className="text-lg font-semibold">Documentación requerida</h2>
          <p className="text-sm text-muted-foreground">
            Suba los documentos que le solicita su abogado. Revise cada archivo y pulse «Listo» para
            enviarlo a revisión; su abogado lo validará antes de darlo por definitivo.
          </p>
        </div>
      </div>

      {req.enRevision > 0 && (
        <div className="mb-4 flex gap-3 rounded-lg border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-900">
          <Clock className="mt-0.5 h-5 w-5 shrink-0" />
          <div>
            <p className="font-medium">
              {req.enRevision} documento{req.enRevision !== 1 ? 's' : ''} en revisión por su abogado
            </p>
            <p className="mt-1 text-amber-800/90">
              Puede seguir subiendo el resto de documentos pendientes. Tras pulsar «Listo», recibirá
              confirmación de envío y el documento quedará bloqueado hasta que su abogado actúe.
            </p>
          </div>
        </div>
      )}

      {req.pendientesSubida > 0 && (
        <p className="mb-4 text-sm text-muted-foreground">
          {req.pendientesSubida} documento{req.pendientesSubida !== 1 ? 's' : ''} pendiente
          {req.pendientesSubida !== 1 ? 's' : ''} de su parte.
        </p>
      )}

      {req.agenteResponsableExpediente === 'cliente' && (
        <div className="mb-4 flex gap-3 rounded-lg border border-primary/20 bg-primary/5 p-4 text-sm">
          <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
          <p>Su abogado le ha solicitado completar documentación pendiente en este expediente.</p>
        </div>
      )}

      <RequerimientosUploadPanel token={token} documentos={req.documentos} />
    </div>
  );
}
