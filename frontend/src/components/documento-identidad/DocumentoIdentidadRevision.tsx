import type { ClienteInput } from '@/api/client';
import { ClienteDatosForm } from '@/components/clientes/ClienteDatosForm';
import { labelsDocumentoIdentidad } from '@/lib/documento-identidad-labels';
import { ETIQUETAS_CAMPO_CLIENTE } from '@/lib/campos-devolucion';

interface DocumentoIdentidadRevisionProps {
  datosIniciales: ClienteInput;
  tipoServicio?: string | null;
  extraccionAutomatica?: boolean;
  camposSoloLectura?: (keyof ClienteInput)[];
  /** Campos que el abogado marcó para revisión guiada. */
  camposResaltados?: string[];
  onConfirmar: (datos: ClienteInput) => void;
  onVolverEscaneo: () => void;
  isSaving?: boolean;
  modo: 'cliente' | 'abogado';
  confirmLabel?: string;
  volverLabel?: string;
}

export function DocumentoIdentidadRevision({
  datosIniciales,
  tipoServicio,
  extraccionAutomatica = false,
  camposSoloLectura = [],
  camposResaltados = [],
  onConfirmar,
  onVolverEscaneo,
  isSaving,
  modo,
  confirmLabel,
  volverLabel,
}: DocumentoIdentidadRevisionProps) {
  const esCliente = modo === 'cliente';
  const labels = labelsDocumentoIdentidad(tipoServicio);
  const docTarjeta = labels.tipoDocumentoCorto;

  return (
    <div className="space-y-4">
      {esCliente && camposResaltados.length > 0 && (
        <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
          <p className="font-semibold">Su abogado le pide revisar:</p>
          <ul className="mt-2 flex flex-wrap gap-1.5">
            {camposResaltados.map((c) => (
              <li
                key={c}
                className="rounded-full border border-amber-400 bg-white px-2.5 py-0.5 text-xs font-medium"
              >
                {ETIQUETAS_CAMPO_CLIENTE[c] ?? c}
              </li>
            ))}
          </ul>
        </div>
      )}
      {esCliente && !extraccionAutomatica && (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
          No se pudieron leer los datos automáticamente del documento. Para {docTarjeta}, asegúrese de que el{' '}
          <strong>reverso</strong> (zona MRZ) sea legible. Complete el formulario manualmente.
        </div>
      )}
      {!esCliente && !extraccionAutomatica && (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
          No se pudieron leer los datos automáticamente del documento. Complete el formulario manualmente.
        </div>
      )}
      <ClienteDatosForm
        initialValues={datosIniciales}
        onSubmit={onConfirmar}
        isSaving={isSaving}
        camposSoloLectura={esCliente ? camposSoloLectura : undefined}
        camposResaltados={camposResaltados}
        tiposDocumentoPermitidos={labels.tipoDocumentoSelect}
        etiquetaNumDocumento={labels.numeroDocumento}
        submitLabel={confirmLabel ?? (esCliente ? 'Confirmar y continuar' : 'Crear cliente')}
        portalCliente={esCliente}
        onVolver={onVolverEscaneo}
        volverLabel={volverLabel ?? 'Volver al escaneo del documento'}
      />
    </div>
  );
}
