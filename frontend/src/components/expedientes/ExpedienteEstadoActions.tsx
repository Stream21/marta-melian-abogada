import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Ban, RotateCcw } from 'lucide-react';
import { api, type ExpedienteResponse } from '@/api/client';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { labelEstadoExpediente, variantEstadoExpediente } from '@/lib/expediente-estado';
import { labelFaseNegocio } from '@/lib/portal-fases';
import { Badge } from '@/components/ui/badge';

interface ExpedienteEstadoActionsProps {
  expediente: ExpedienteResponse;
}

export function ExpedienteEstadoActions({ expediente }: ExpedienteEstadoActionsProps) {
  const queryClient = useQueryClient();
  const [cancelOpen, setCancelOpen] = useState(false);
  const [reabrirOpen, setReabrirOpen] = useState(false);
  const [motivo, setMotivo] = useState('');

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: ['expediente', expediente.id] });
    void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
    void queryClient.invalidateQueries({ queryKey: ['auditoria', expediente.id] });
  };

  const cancelarMutation = useMutation({
    mutationFn: () => api.cancelarExpediente(expediente.id, motivo.trim() || undefined),
    onSuccess: () => {
      setCancelOpen(false);
      setMotivo('');
      invalidate();
    },
  });

  const reabrirMutation = useMutation({
    mutationFn: () => api.reabrirExpediente(expediente.id),
    onSuccess: () => {
      setReabrirOpen(false);
      invalidate();
    },
  });

  const errorMsg =
    (cancelarMutation.error instanceof Error && cancelarMutation.error.message) ||
    (reabrirMutation.error instanceof Error && reabrirMutation.error.message) ||
    null;

  const faseActual = expediente.faseNegocio
    ? labelFaseNegocio(expediente.faseNegocio)
    : 'la fase actual';

  return (
    <div className="flex flex-wrap items-center gap-2">
      <Badge variant={variantEstadoExpediente(expediente.estado)}>
        {expediente.estadoLabel || labelEstadoExpediente(expediente.estado)}
      </Badge>

      {expediente.estado === 'abierto' && (
        <Button type="button" variant="outline" size="sm" onClick={() => setCancelOpen(true)}>
          <Ban className="h-4 w-4" />
          Cancelar
        </Button>
      )}

      {expediente.estado === 'cancelado' && (
        <Button type="button" variant="outline" size="sm" onClick={() => setReabrirOpen(true)}>
          <RotateCcw className="h-4 w-4" />
          Reabrir
        </Button>
      )}

      {errorMsg && (
        <p className="w-full text-sm text-destructive" role="alert">
          {errorMsg}
        </p>
      )}

      <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Cancelar expediente</DialogTitle>
            <DialogDescription>
              El cliente deja el proceso a medias. Al reabrir, continuará en la misma fase (
              {faseActual}).
            </DialogDescription>
          </DialogHeader>
          <label className="block space-y-1.5 text-sm">
            <span className="font-medium text-foreground">Motivo (opcional)</span>
            <textarea
              className="input-field min-h-[88px] w-full resize-y"
              value={motivo}
              onChange={(e) => setMotivo(e.target.value)}
              placeholder="Ej. el cliente no continúa con la contratación"
            />
          </label>
          <DialogFooter>
            <Button type="button" variant="ghost" onClick={() => setCancelOpen(false)}>
              Volver
            </Button>
            <Button
              type="button"
              variant="destructive"
              disabled={cancelarMutation.isPending}
              onClick={() => cancelarMutation.mutate()}
            >
              {cancelarMutation.isPending ? 'Cancelando…' : 'Confirmar cancelación'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={reabrirOpen} onOpenChange={setReabrirOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Reabrir expediente</DialogTitle>
            <DialogDescription>
              El expediente volverá a estar abierto en <strong>{faseActual}</strong>, en el mismo
              punto del flujo en que se canceló.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button type="button" variant="ghost" onClick={() => setReabrirOpen(false)}>
              Volver
            </Button>
            <Button
              type="button"
              disabled={reabrirMutation.isPending}
              onClick={() => reabrirMutation.mutate()}
            >
              {reabrirMutation.isPending ? 'Reabriendo…' : 'Reabrir expediente'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
