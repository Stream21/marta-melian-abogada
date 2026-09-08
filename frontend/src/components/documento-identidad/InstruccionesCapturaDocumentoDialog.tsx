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
import { cn } from '@/lib/utils';
import {
  CAPTURA_CONSEJO,
  CAPTURA_LADO_AYUDA,
  CAPTURA_LADO_TITULO,
} from './captura-lado-textos';
import { DocumentoLadoGuia } from './DocumentoLadoGuia';
import type { LadoCapturaCamara } from './CapturaCamaraDocumento';

interface InstruccionesCapturaDocumentoDialogProps {
  abierto: boolean;
  lado: LadoCapturaCamara;
  etiquetaDocumento?: string;
  onContinuar: () => void;
  onCancelar: () => void;
}

export function InstruccionesCapturaDocumentoDialog({
  abierto,
  lado,
  onContinuar,
  onCancelar,
}: InstruccionesCapturaDocumentoDialogProps) {
  return (
    <Dialog open={abierto} onOpenChange={(open) => !open && onCancelar()}>
      <DialogContent
        className={cn(
          'flex h-[100dvh] max-h-[100dvh] w-full max-w-none flex-col gap-0 overflow-hidden rounded-none border-0 p-0',
          'left-0 top-0 translate-x-0 translate-y-0',
          'sm:left-[50%] sm:top-[50%] sm:h-auto sm:max-h-[92vh] sm:max-w-md sm:translate-x-[-50%] sm:translate-y-[-50%] sm:rounded-xl sm:border sm:p-0',
        )}
      >
        <div className="flex min-h-0 flex-1 flex-col overflow-y-auto px-5 pb-4 pt-14 sm:pt-12">
          <DialogHeader className="space-y-2 text-center sm:text-center">
            <DialogTitle className="text-xl">{CAPTURA_LADO_TITULO[lado]}</DialogTitle>
            <DialogDescription className="text-center text-base">
              {CAPTURA_LADO_AYUDA[lado]}
            </DialogDescription>
          </DialogHeader>

          <DocumentoLadoGuia lado={lado} variant="dialogo" className="my-6 flex-1 justify-center py-2" />

          <p className="text-center text-sm text-muted-foreground">{CAPTURA_CONSEJO}</p>
        </div>

        <DialogFooter className="shrink-0 flex-col gap-2 border-t border-border bg-card px-5 py-4 sm:flex-col">
          <Button type="button" size="lg" className="w-full" onClick={onContinuar}>
            <Camera className="mr-2 h-5 w-5" />
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
