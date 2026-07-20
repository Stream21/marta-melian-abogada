import { useState } from 'react';
import { Loader2, UserRound } from 'lucide-react';
import type { RequerimientosDocumentoResponse } from '@/api/client';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

interface RequerimientosDerivarClienteModalProps {
  doc: RequerimientosDocumentoResponse | null;
  open: boolean;
  onClose: () => void;
  onConfirm: (docId: string, nota: string) => void;
  pending?: boolean;
  error?: string | null;
}

export function RequerimientosDerivarClienteModal({
  doc,
  open,
  onClose,
  onConfirm,
  pending = false,
  error = null,
}: RequerimientosDerivarClienteModalProps) {
  const [nota, setNota] = useState('');

  const handleOpenChange = (next: boolean) => {
    if (!next) {
      setNota('');
      onClose();
    }
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <UserRound className="h-5 w-5 text-primary" />
            Derivar al cliente
          </DialogTitle>
          <DialogDescription>
            {doc ? (
              <>
                El requisito «{doc.nombre}» pasará a estar pendiente del cliente. Se le enviará un
                aviso por email si tiene correo registrado.
              </>
            ) : (
              'Seleccione un documento.'
            )}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-2">
          <label htmlFor="nota-derivar" className="text-sm font-medium">
            Mensaje para el cliente (opcional)
          </label>
          <textarea
            id="nota-derivar"
            value={nota}
            onChange={(e) => setNota(e.target.value)}
            placeholder="Indique qué debe aportar o completar el cliente…"
            rows={4}
            disabled={pending}
            className={cn(
              'flex min-h-[96px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
              'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none',
              'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            )}
          />
          {nota.trim().length > 0 && nota.trim().length < 5 && (
            <p className="text-xs text-muted-foreground">
              Si escribe un mensaje, use al menos 5 caracteres.
            </p>
          )}
        </div>

        {error && <p className="text-sm text-destructive">{error}</p>}

        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => handleOpenChange(false)} disabled={pending}>
            Cancelar
          </Button>
          <Button
            type="button"
            disabled={pending || !doc || (nota.trim().length > 0 && nota.trim().length < 5)}
            onClick={() => doc && onConfirm(doc.id, nota.trim())}
          >
            {pending ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Derivando…
              </>
            ) : (
              'Derivar al cliente'
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
