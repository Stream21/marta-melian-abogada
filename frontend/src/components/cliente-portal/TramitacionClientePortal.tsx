import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ExternalLink, FileUp, Loader2, MessageSquare } from 'lucide-react';
import {
  api,
  type AccesoExpedienteResponse,
  type AccesoTramitacionRequerimientoResponse,
} from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface TramitacionClientePortalProps {
  token: string;
  data: AccesoExpedienteResponse;
}

export function TramitacionClientePortal({ token, data }: TramitacionClientePortalProps) {
  const tramitacion = data.tramitacion;
  if (!tramitacion) {
    return (
      <p className="py-8 text-center text-sm text-muted-foreground">
        La información de tramitación aún no está disponible.
      </p>
    );
  }

  const pendientes = tramitacion.requerimientosCliente.filter((r) => r.puedeSubir);
  const seguimiento = tramitacion.instruccionesSeguimiento;

  return (
    <div className="space-y-6">
      <section className="space-y-3 rounded-xl border border-border bg-card p-5 shadow-sm">
        <p className="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
          Estado de su solicitud
        </p>
        <h2 className="text-xl font-semibold text-foreground">{tramitacion.estadoClienteLabel}</h2>
        <p className="text-sm text-muted-foreground">
          {tramitacion.estadoCliente === 'pendiente_tramitacion' &&
            'Su abogado ya ha presentado la solicitud. Cuando la Administración asigne el número de expediente, podrá consultarlo desde este portal.'}
          {tramitacion.estadoCliente === 'en_seguimiento' &&
            'Su solicitud está en tramitación. Use el enlace de la sede o el SMS para consultar el estado.'}
          {tramitacion.estadoCliente === 'accion_requerida' &&
            'Hay documentación pendiente por su parte. Complétela a continuación.'}
          {tramitacion.estadoCliente === 'en_tramite_despacho' &&
            'Su abogado está gestionando un requerimiento de la Administración. No necesita hacer nada ahora.'}
          {tramitacion.estadoCliente === 'preparacion' &&
            'Su abogado está preparando la presentación telemática.'}
        </p>
      </section>

      {seguimiento && (
        <section className="space-y-3 rounded-xl border border-primary/20 bg-primary/5 p-5 shadow-sm">
          <h3 className="font-semibold">Consulte usted el seguimiento</h3>
          <p className="text-sm text-muted-foreground">{seguimiento.texto}</p>
          {seguimiento.numeroExpedienteExtranjeria && (
            <p className="text-sm">
              <span className="text-muted-foreground">Nº expediente de extranjería: </span>
              <span className="font-semibold tracking-wide">
                {seguimiento.numeroExpedienteExtranjeria}
              </span>
            </p>
          )}
          <div className="flex flex-wrap gap-2 pt-1">
            <Button asChild size="sm" className="min-h-11">
              <a href={seguimiento.webUrl} target="_blank" rel="noreferrer">
                <ExternalLink className="mr-1.5 h-4 w-4" />
                Abrir consulta en la sede
              </a>
            </Button>
            {seguimiento.sms && (
              <Badge variant="secondary" className="gap-1.5 py-2">
                <MessageSquare className="h-3.5 w-3.5" />
                SMS: {seguimiento.sms}
              </Badge>
            )}
          </div>
        </section>
      )}

      {pendientes.length > 0 && (
        <section className="space-y-3">
          <h3 className="font-semibold">Documentación requerida</h3>
          {pendientes.map((req) => (
            <RequerimientoClienteUpload key={req.id} token={token} req={req} />
          ))}
        </section>
      )}

      {tramitacion.requerimientosCliente.length > 0 && pendientes.length === 0 && (
        <p className="text-sm text-muted-foreground">
          No tiene documentos pendientes de envío en este momento.
        </p>
      )}
    </div>
  );
}

function RequerimientoClienteUpload({
  token,
  req,
}: {
  token: string;
  req: AccesoTramitacionRequerimientoResponse;
}) {
  const queryClient = useQueryClient();
  const [file, setFile] = useState<File | null>(null);

  const mutation = useMutation({
    mutationFn: () => {
      if (!file) throw new Error('Seleccione un archivo');
      return api.subirArchivoRequerimientoMercurioPortal(token, req.id, file);
    },
    onSuccess: () => {
      setFile(null);
      void queryClient.invalidateQueries({ queryKey: ['acceso', token] });
    },
  });

  return (
    <div className="space-y-3 rounded-xl border border-border bg-card p-4">
      <div>
        <p className="font-medium">{req.nombre}</p>
        {req.descripcion && (
          <p className="mt-1 text-sm text-muted-foreground">{req.descripcion}</p>
        )}
        <Badge variant="warning" className="mt-2">
          {req.estadoLabel}
        </Badge>
      </div>
      <div className="space-y-2">
        <Label htmlFor={`req-${req.id}`}>Adjuntar documento</Label>
        <Input
          id={`req-${req.id}`}
          type="file"
          accept=".pdf,application/pdf,image/*"
          onChange={(e) => setFile(e.target.files?.[0] ?? null)}
        />
      </div>
      {mutation.error && (
        <p className="text-sm text-destructive">
          {mutation.error instanceof Error ? mutation.error.message : 'Error al subir'}
        </p>
      )}
      <Button
        className="min-h-11 w-full"
        disabled={!file || mutation.isPending}
        onClick={() => mutation.mutate()}
      >
        {mutation.isPending ? (
          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
        ) : (
          <FileUp className="mr-2 h-4 w-4" />
        )}
        Enviar documento
      </Button>
    </div>
  );
}
