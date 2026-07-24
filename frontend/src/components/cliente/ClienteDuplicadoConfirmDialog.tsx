import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

export type CampoDuplicadoCliente = 'telefono' | 'documento';

interface ClienteDuplicadoConfirmDialogProps {
  open: boolean;
  clienteNombre: string;
  campo?: CampoDuplicadoCliente | string;
  loading?: boolean;
  /** Error de la confirmación (p. ej. al reintentar con permitirDuplicado). */
  error?: string | null;
  onCancel: () => void;
  onConfirm: () => void;
  /** Texto del botón de confirmación */
  confirmLabel?: string;
}

function descripcionCampo(campo?: string): string {
  if (campo === 'documento') {
    return 'documento de identidad';
  }
  if (campo === 'telefono') {
    return 'teléfono';
  }
  return 'teléfono o documento';
}

export function ClienteDuplicadoConfirmDialog({
  open,
  clienteNombre,
  campo,
  loading = false,
  error = null,
  onCancel,
  onConfirm,
  confirmLabel = 'Continuar de todos modos',
}: ClienteDuplicadoConfirmDialogProps) {
  const queCoincide = descripcionCampo(campo);

  return (
    <Dialog open={open} onOpenChange={(next) => !next && !loading && onCancel()}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Cliente ya registrado</DialogTitle>
          <DialogDescription asChild>
            <div className="space-y-2 text-sm text-muted-foreground">
              <p>
                Ya existe el cliente <strong className="text-foreground">{clienteNombre || 'otro cliente'}</strong> con el
                mismo {queCoincide}.
              </p>
              {campo === 'telefono' && (
                <p>
                  Si continúa, el teléfono se asignará a este expediente y se quitará del cliente
                  anterior.
                </p>
              )}
              <p>¿Desea continuar con los datos de este expediente de todos modos?</p>
            </div>
          </DialogDescription>
        </DialogHeader>
        {error && (
          <p className="text-sm text-destructive" role="alert">
            {error}
          </p>
        )}
        <DialogFooter>
          <Button type="button" variant="outline" onClick={onCancel} disabled={loading}>
            Cancelar
          </Button>
          <Button type="button" onClick={onConfirm} disabled={loading}>
            {loading ? 'Guardando…' : confirmLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
