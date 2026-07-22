import { Camera } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogDescription,
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
  anverso: 'Prepare el anverso',
  reverso: 'Prepare el reverso',
  pasaporte: 'Prepare el pasaporte',
};

const AYUDA: Record<LadoCapturaCamara, string> = {
  anverso: 'Coloque la tarjeta en horizontal, con la foto hacia arriba.',
  reverso: 'Gire la tarjeta. La banda negra inferior debe verse completa.',
  pasaporte: 'Abra la página interior con su foto y datos.',
};

export function InstruccionesCapturaDocumentoDialog({
  abierto,
  lado,
  etiquetaDocumento,
  onContinuar,
  onCancelar,
}: InstruccionesCapturaDocumentoDialogProps) {
  const doc = etiquetaDocumento?.trim();

  return (
    <Dialog open={abierto} onOpenChange={(open) => !open && onCancelar()}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-sm">
        <DialogHeader className="space-y-1 text-center sm:text-center">
          {doc && (
            <p className="section-label justify-center text-center sm:justify-center">{doc}</p>
          )}
          <DialogTitle>{TITULO[lado]}</DialogTitle>
          <DialogDescription className="text-center">{AYUDA[lado]}</DialogDescription>
        </DialogHeader>

        <DocumentoLadoGuia lado={lado} />

        <p className="text-center text-xs text-muted-foreground">
          Evite reflejos y sombras. El documento debe verse entero en el marco.
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
