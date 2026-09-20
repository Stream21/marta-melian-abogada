import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

export type CampoDetectadoCliente = 'telefono' | 'email';

interface ClienteExistenteDetectadoDialogProps {
  open: boolean;
  clienteNombre: string;
  campo: CampoDetectadoCliente;
  detalle?: string | null;
  loading?: boolean;
  onUsarExistente: () => void;
  onContinuarNuevo: () => void;
  onCancel: () => void;
}

function etiquetaCampo(campo: CampoDetectadoCliente): string {
  return campo === 'email' ? 'email' : 'teléfono';
}

export function ClienteExistenteDetectadoDialog({
  open,
  clienteNombre,
  campo,
  detalle,
  loading = false,
  onUsarExistente,
  onContinuarNuevo,
  onCancel,
}: ClienteExistenteDetectadoDialogProps) {
  return (
    <Dialog open={open} onOpenChange={(next) => !next && !loading && onCancel()}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Cliente ya registrado</DialogTitle>
          <DialogDescription asChild>
            <div className="space-y-2 text-sm text-muted-foreground">
              <p>
                Ya existe el cliente{' '}
                <strong className="text-foreground">{clienteNombre || 'otro cliente'}</strong> con el
                mismo {etiquetaCampo(campo)}
                {detalle ? (
                  <>
                    {' '}
                    (<span className="font-mono text-foreground">{detalle}</span>)
                  </>
                ) : null}
                .
              </p>
              <p>
                Puede vincularlo a este expediente y continuar con sus datos, o seguir dando de alta
                un cliente nuevo.
              </p>
            </div>
          </DialogDescription>
        </DialogHeader>
        <DialogFooter className="flex-col gap-2 sm:flex-row sm:justify-end">
          <Button type="button" variant="outline" onClick={onContinuarNuevo} disabled={loading}>
            Continuar como nuevo
          </Button>
          <Button type="button" onClick={onUsarExistente} disabled={loading}>
            Usar este cliente
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
