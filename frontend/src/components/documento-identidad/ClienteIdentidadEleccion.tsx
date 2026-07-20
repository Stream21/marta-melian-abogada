import { Loader2, ScanLine, UserCheck } from 'lucide-react';
import type { AccesoIdentidadEdicionResponse } from '@/api/client';
import { Button } from '@/components/ui/button';

interface ClienteIdentidadEleccionProps {
  identidadEdicion: AccesoIdentidadEdicionResponse;
  onReutilizar: () => void;
  onEscanearNuevo: () => void;
  reutilizando?: boolean;
}

export function ClienteIdentidadEleccion({
  identidadEdicion,
  onReutilizar,
  onEscanearNuevo,
  reutilizando,
}: ClienteIdentidadEleccionProps) {
  return (
    <div className="panel space-y-5 p-6">
      <div>
        <h3 className="text-base font-semibold">Ya tenemos su documento de identidad</h3>
        <p className="mt-1 text-sm text-muted-foreground">
          Usted ya ha trabajado con nosotros en un trámite anterior. Puede reutilizar el documento
          registrado o escanear uno nuevo si ha cambiado (renovación, cambio de domicilio en el DNI,
          etc.).
        </p>
      </div>

      {(identidadEdicion.anversoUrl || identidadEdicion.reversoUrl) && (
        <div className="flex flex-wrap gap-3 rounded-lg border bg-muted/20 p-3">
          {identidadEdicion.anversoUrl && (
            <div className="text-center">
              <img
                src={identidadEdicion.anversoUrl}
                alt="Documento registrado — anverso"
                className="h-20 w-32 rounded border object-contain bg-card"
              />
              <p className="mt-1 text-[10px] text-muted-foreground">Documento registrado</p>
            </div>
          )}
          {identidadEdicion.reversoUrl && (
            <div className="text-center">
              <img
                src={identidadEdicion.reversoUrl}
                alt="Documento registrado — reverso"
                className="h-20 w-32 rounded border object-contain bg-card"
              />
              <p className="mt-1 text-[10px] text-muted-foreground">Reverso registrado</p>
            </div>
          )}
        </div>
      )}

      <div className="grid gap-3 sm:grid-cols-2">
        <Button
          type="button"
          size="lg"
          className="h-auto flex-col items-start gap-2 whitespace-normal px-4 py-4 text-left"
          onClick={onReutilizar}
          disabled={reutilizando}
        >
          <span className="flex items-center gap-2 font-semibold">
            {reutilizando ? (
              <Loader2 className="h-5 w-5 shrink-0 animate-spin" />
            ) : (
              <UserCheck className="h-5 w-5 shrink-0" />
            )}
            Continuar sin escanear
          </span>
          <span className="text-xs font-normal opacity-90">
            Usamos su documento y datos ya registrados. Pasará directamente al siguiente paso.
          </span>
        </Button>

        <Button
          type="button"
          variant="outline"
          size="lg"
          className="h-auto flex-col items-start gap-2 whitespace-normal px-4 py-4 text-left"
          onClick={onEscanearNuevo}
          disabled={reutilizando}
        >
          <span className="flex items-center gap-2 font-semibold">
            <ScanLine className="h-5 w-5 shrink-0" />
            Escanear documento nuevo
          </span>
          <span className="text-xs font-normal text-muted-foreground">
            Suba un documento actualizado. Los datos extraídos sustituirán los del formulario.
          </span>
        </Button>
      </div>
    </div>
  );
}
