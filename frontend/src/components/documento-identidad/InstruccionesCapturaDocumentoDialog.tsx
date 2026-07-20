import { Camera } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { DocumentoLadoGuia } from './DocumentoLadoGuia';
import type { LadoCapturaCamara } from './CapturaCamaraDocumento';

interface InstruccionesCapturaDocumentoDialogProps {
  abierto: boolean;
  lado: LadoCapturaCamara;
  etiquetaDocumento?: string;
  onContinuar: () => void;
  onCancelar: () => void;
}

const TITULO: Record<LadoCapturaCamara, string> = {
  anverso: 'Fotografiar anverso',
  reverso: 'Fotografiar reverso',
  pasaporte: 'Fotografiar pasaporte',
};

export function InstruccionesCapturaDocumentoDialog({
  abierto,
  lado,
  onContinuar,
  onCancelar,
}: InstruccionesCapturaDocumentoDialogProps) {
  return (
    <Dialog open={abierto} onOpenChange={(open) => !open && onCancelar()}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-sm">
        <DialogHeader className="text-center sm:text-center">
          <DialogTitle>{TITULO[lado]}</DialogTitle>
        </DialogHeader>

        <DocumentoLadoGuia lado={lado} mostrarZonasLeidas />

        <p className="text-center text-xs text-muted-foreground">
          Las zonas marcadas en verde se leerán automáticamente. Evite reflejos y sombras.
        </p>

        <DialogFooter className="flex-col gap-2 sm:flex-col">
          <Button type="button" size="lg" className="w-full" onClick={onContinuar}>
            <Camera className="mr-2 h-4 w-4" />
            Abrir cámara
          </Button>
          <Button type="button" variant="ghost" className="w-full" onClick={onCancelar}>
            Cancelar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
