import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ShieldCheck } from 'lucide-react';
import { api } from '@/api/client';
import { Label } from '@/components/ui/label';

interface TramiteContratacionOtpPanelProps {
  tramiteId: string;
}

export function TramiteContratacionOtpPanel({ tramiteId }: TramiteContratacionOtpPanelProps) {
  const queryClient = useQueryClient();
  const { data: tramite, isLoading } = useQuery({
    queryKey: ['tramite', tramiteId],
    queryFn: () => api.getTramite(tramiteId),
  });

  const updateMutation = useMutation({
    mutationFn: (requiereOtpFirma: boolean) => {
      if (!tramite) throw new Error('Trámite no cargado');
      return api.putTramite(tramiteId, {
        servicioId: tramite.servicioId,
        nombre: tramite.nombre,
        honorarios: tramite.honorarios,
        plataforma: tramite.plataforma,
        requiereProcurador: tramite.requiereProcurador,
        requiereOtpFirma,
      });
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['tramite', tramiteId] });
      void queryClient.invalidateQueries({ queryKey: ['tramites'] });
    },
  });

  if (isLoading || !tramite) {
    return null;
  }

  return (
    <div className="panel mb-4 p-5">
      <div className="flex items-start gap-3">
        <ShieldCheck className="mt-0.5 h-5 w-5 text-primary shrink-0" />
        <div className="flex-1 space-y-2">
          <div>
            <p className="font-medium">Verificación OTP en contratación (fase 1)</p>
            <p className="text-sm text-muted-foreground mt-1">
              Si está activo, el cliente debe validar un código SMS en su móvil antes de firmar los documentos
              legales. Desactive solo si lo acordáis con la abogada para casos excepcionales.
            </p>
          </div>
          <label className="flex items-start gap-3 rounded-lg border border-border bg-muted/30 px-4 py-3 cursor-pointer">
            <input
              type="checkbox"
              className="mt-0.5 h-4 w-4"
              checked={tramite.requiereOtpFirma}
              disabled={updateMutation.isPending}
              onChange={(e) => updateMutation.mutate(e.target.checked)}
            />
            <div>
              <Label className="cursor-pointer">Requerir OTP SMS al firmar</Label>
              <p className="text-xs text-muted-foreground mt-0.5">
                Por defecto activado. Refuerza la firma electrónica simple sin servicios externos.
              </p>
            </div>
          </label>
          {updateMutation.isError && (
            <p className="text-sm text-destructive">
              {updateMutation.error instanceof Error ? updateMutation.error.message : 'Error al guardar.'}
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
