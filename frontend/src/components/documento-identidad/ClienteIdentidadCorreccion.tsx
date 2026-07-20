import { FileImage, FormInput, ScanLine } from 'lucide-react';
import type { AccesoIdentidadEdicionResponse, MotivoDevolucionIdentidad } from '@/api/client';
import { labelsDocumentoIdentidad } from '@/lib/documento-identidad-labels';
import { cn } from '@/lib/utils';

const MOTIVO_LABELS: Record<MotivoDevolucionIdentidad, string> = {
  datos_personales: 'Corregir datos personales',
  documento_anverso: 'Nueva foto del anverso',
  documento_reverso: 'Nueva foto del reverso',
  documento_completo: 'Actualizar documento completo',
  documentacion_adicional: 'Documentación adicional del trámite',
};

interface ClienteIdentidadCorreccionProps {
  identidadEdicion: AccesoIdentidadEdicionResponse;
  notaDevolucion?: string | null;
  tipoServicio?: string | null;
  onCorregirDatos: () => void;
  onActualizarDocumento: (lado?: 'anverso' | 'reverso' | 'completo') => void;
}

export function ClienteIdentidadCorreccion({
  identidadEdicion,
  notaDevolucion,
  tipoServicio,
  onCorregirDatos,
  onActualizarDocumento,
}: ClienteIdentidadCorreccionProps) {
  const labels = labelsDocumentoIdentidad(tipoServicio);
  const motivos = identidadEdicion.motivosDevolucion ?? [];
  const sugerirSoloDatos = motivos.includes('datos_personales') && motivos.length === 1;
  const sugerirReverso = motivos.includes('documento_reverso');
  const sugerirAnverso = motivos.includes('documento_anverso');

  return (
    <div className="panel space-y-4 p-6">
      <div>
        <h3 className="text-base font-semibold">Correcciones solicitadas</h3>
        <p className="mt-1 text-sm text-muted-foreground">
          Su abogado ha revisado su información. Puede corregir solo lo necesario sin empezar de cero.
        </p>
      </div>

      {motivos.length > 0 && (
        <ul className="flex flex-wrap gap-2">
          {motivos.map((m) => (
            <li
              key={m}
              className="rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-900"
            >
              {MOTIVO_LABELS[m] ?? m}
            </li>
          ))}
        </ul>
      )}

      {notaDevolucion && (
        <p className="rounded-lg border border-amber-200 bg-amber-50/80 px-3 py-2 text-sm text-amber-900 whitespace-pre-wrap">
          {notaDevolucion}
        </p>
      )}

      <div className="grid gap-3 sm:grid-cols-2">
        <button
          type="button"
          onClick={onCorregirDatos}
          className={cn(
            'flex items-start gap-3 rounded-lg border p-4 text-left transition-colors hover:border-primary hover:bg-primary/5',
            sugerirSoloDatos && 'border-primary/40 bg-primary/5',
          )}
        >
          <FormInput className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
          <div>
            <p className="font-medium">Corregir solo mis datos</p>
            <p className="text-xs text-muted-foreground">
              Mantiene las fotos del documento ya enviadas. Ideal si solo hay que cambiar domicilio, email u otros datos.
            </p>
          </div>
        </button>

        {sugerirReverso && !sugerirAnverso ? (
          <button
            type="button"
            onClick={() => onActualizarDocumento('reverso')}
            className="flex items-start gap-3 rounded-lg border border-primary/40 bg-primary/5 p-4 text-left transition-colors hover:border-primary hover:bg-primary/10"
          >
            <ScanLine className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
            <div>
              <p className="font-medium">Actualizar solo el reverso</p>
              <p className="text-xs text-muted-foreground">
                Vuelva a fotografiar la cara con la banda MRZ. Sus datos del formulario se conservan.
              </p>
            </div>
          </button>
        ) : sugerirAnverso && !sugerirReverso ? (
          <button
            type="button"
            onClick={() => onActualizarDocumento('anverso')}
            className="flex items-start gap-3 rounded-lg border border-primary/40 bg-primary/5 p-4 text-left transition-colors hover:border-primary hover:bg-primary/10"
          >
            <ScanLine className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
            <div>
              <p className="font-medium">Actualizar solo el anverso</p>
              <p className="text-xs text-muted-foreground">Nueva foto de la cara con su fotografía.</p>
            </div>
          </button>
        ) : (
          <button
            type="button"
            onClick={() => onActualizarDocumento('completo')}
            className="flex items-start gap-3 rounded-lg border p-4 text-left transition-colors hover:border-primary hover:bg-primary/5"
          >
            <ScanLine className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
            <div>
              <p className="font-medium">Actualizar documento</p>
              <p className="text-xs text-muted-foreground">
                Sustituya una o ambas imágenes de su {labels.tipoDocumentoCorto} o pasaporte. Después podrá revisar los datos.
              </p>
            </div>
          </button>
        )}

        <button
          type="button"
          onClick={() => onActualizarDocumento(sugerirReverso ? 'reverso' : sugerirAnverso ? 'anverso' : 'completo')}
          className="flex items-start gap-3 rounded-lg border p-4 text-left transition-colors hover:border-primary hover:bg-primary/5 sm:col-span-2"
        >
          <FileImage className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
          <div>
            <p className="font-medium">Ver documento actual y decidir</p>
            <p className="text-xs text-muted-foreground">
              Revise las imágenes ya enviadas y elija si necesita cambiar el anverso, el reverso o ambos.
            </p>
          </div>
        </button>
      </div>

      {(identidadEdicion.anversoUrl || identidadEdicion.reversoUrl) && (
        <div className="flex flex-wrap gap-3 rounded-lg border bg-muted/20 p-3">
          {identidadEdicion.anversoUrl && (
            <div className="text-center">
              <img
                src={identidadEdicion.anversoUrl}
                alt="Anverso actual"
                className="h-20 w-32 rounded border object-contain bg-white"
              />
              <p className="mt-1 text-[10px] text-muted-foreground">Anverso actual</p>
            </div>
          )}
          {identidadEdicion.reversoUrl && (
            <div className="text-center">
              <img
                src={identidadEdicion.reversoUrl}
                alt="Reverso actual"
                className="h-20 w-32 rounded border object-contain bg-white"
              />
              <p className="mt-1 text-[10px] text-muted-foreground">Reverso actual</p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
