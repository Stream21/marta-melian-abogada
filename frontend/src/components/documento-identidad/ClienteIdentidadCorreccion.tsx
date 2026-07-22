import { FileImage, FormInput, ScanLine } from 'lucide-react';
import type { AccesoIdentidadEdicionResponse } from '@/api/client';
import { labelsDocumentoIdentidad } from '@/lib/documento-identidad-labels';
import {
  ETIQUETAS_CAMPO_CLIENTE,
  analizarDevolucionIdentidad,
  type LadoDocumentoDevolucion,
} from '@/lib/campos-devolucion';
import { cn } from '@/lib/utils';

interface ClienteIdentidadCorreccionProps {
  identidadEdicion: AccesoIdentidadEdicionResponse;
  notaDevolucion?: string | null;
  tipoServicio?: string | null;
  onCorregirDatos: () => void;
  onActualizarDocumento: (lado?: LadoDocumentoDevolucion) => void;
}

export function ClienteIdentidadCorreccion({
  identidadEdicion,
  notaDevolucion,
  tipoServicio,
  onCorregirDatos,
  onActualizarDocumento,
}: ClienteIdentidadCorreccionProps) {
  const labels = labelsDocumentoIdentidad(tipoServicio);
  const analisis = analizarDevolucionIdentidad(identidadEdicion.motivosDevolucion);
  const { campos, necesitaDatos, necesitaDocumento, ladoDocumento, documentacionAdicional } =
    analisis;

  const etiquetaScan =
    ladoDocumento === 'anverso'
      ? 'Fotografiar el anverso'
      : ladoDocumento === 'reverso'
        ? 'Fotografiar el reverso'
        : `Fotografiar el ${labels.tipoDocumentoCorto}`;

  const ayudaScan =
    ladoDocumento === 'anverso'
      ? 'Su abogado pide una nueva foto de la cara con su imagen. El reverso se conserva.'
      : ladoDocumento === 'reverso'
        ? 'Su abogado pide una nueva foto de la cara con la banda MRZ. El anverso se conserva.'
        : 'Vuelva a escanear anverso y reverso con buena luz y el documento entero en el marco.';

  return (
    <div className="panel space-y-4 p-6">
      <div>
        <h3 className="text-base font-semibold">Correcciones solicitadas</h3>
        <p className="mt-1 text-sm text-muted-foreground">
          Elija la acción que corresponde a lo que le ha pedido su abogado.
        </p>
      </div>

      {campos.length > 0 && (
        <div className="rounded-lg border border-amber-300 bg-amber-50/90 px-3 py-3">
          <p className="text-sm font-semibold text-amber-950">Campos a revisar</p>
          <ul className="mt-2 flex flex-wrap gap-1.5">
            {campos.map((c) => (
              <li
                key={c}
                className="rounded-full border border-amber-400 bg-white px-2.5 py-0.5 text-xs font-medium text-amber-950"
              >
                {ETIQUETAS_CAMPO_CLIENTE[c] ?? c}
              </li>
            ))}
          </ul>
        </div>
      )}

      {notaDevolucion && (
        <p className="whitespace-pre-wrap rounded-lg border border-amber-200 bg-amber-50/80 px-3 py-2 text-sm text-amber-900">
          {notaDevolucion}
        </p>
      )}

      <div className="grid gap-3">
        {necesitaDocumento && ladoDocumento && (
          <button
            type="button"
            onClick={() => onActualizarDocumento(ladoDocumento)}
            className={cn(
              'flex items-start gap-3 rounded-xl border-2 border-primary/40 bg-primary/5 p-4 text-left transition-colors',
              'hover:border-primary hover:bg-primary/10',
            )}
          >
            <ScanLine className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
            <div>
              <p className="font-semibold text-foreground">{etiquetaScan}</p>
              <p className="mt-1 text-xs text-muted-foreground">{ayudaScan}</p>
              {necesitaDatos && (
                <p className="mt-2 text-xs font-medium text-primary">
                  Después podrá revisar también los datos del formulario.
                </p>
              )}
            </div>
          </button>
        )}

        {necesitaDatos && (
          <button
            type="button"
            onClick={onCorregirDatos}
            className={cn(
              'flex items-start gap-3 rounded-xl border p-4 text-left transition-colors hover:border-primary hover:bg-primary/5',
              !necesitaDocumento && 'border-primary/40 bg-primary/5',
            )}
          >
            <FormInput className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
            <div>
              <p className="font-medium">
                {campos.length > 0 ? 'Corregir los campos indicados' : 'Corregir mis datos'}
              </p>
              <p className="mt-1 text-xs text-muted-foreground">
                {necesitaDocumento
                  ? 'Si ya tiene buenas fotos, puede corregir solo el formulario.'
                  : 'Mantiene las fotos del documento. Los campos marcados aparecerán resaltados.'}
              </p>
            </div>
          </button>
        )}

        {!necesitaDocumento && !necesitaDatos && (
          <button
            type="button"
            onClick={() => onActualizarDocumento('completo')}
            className="flex items-start gap-3 rounded-xl border border-primary/40 bg-primary/5 p-4 text-left transition-colors hover:border-primary"
          >
            <FileImage className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
            <div>
              <p className="font-medium">Actualizar documento ({labels.tipoDocumentoCorto})</p>
              <p className="mt-1 text-xs text-muted-foreground">
                Escanee de nuevo el documento o corrija los datos si hace falta.
              </p>
            </div>
          </button>
        )}

        {!necesitaDocumento && !necesitaDatos && (
          <button
            type="button"
            onClick={onCorregirDatos}
            className="flex items-start gap-3 rounded-xl border p-4 text-left transition-colors hover:border-primary hover:bg-primary/5"
          >
            <FormInput className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
            <div>
              <p className="font-medium">Corregir solo mis datos</p>
              <p className="mt-1 text-xs text-muted-foreground">
                Mantiene las fotos del documento ya enviadas.
              </p>
            </div>
          </button>
        )}
      </div>

      {documentacionAdicional && (
        <p className="rounded-lg border border-border bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
          También revise la documentación adicional del trámite más abajo en esta misma pantalla.
        </p>
      )}
    </div>
  );
}
