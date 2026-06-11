import type { ClienteInput } from '@/api/client';
import { ClienteDatosForm } from '@/components/clientes/ClienteDatosForm';
import { Button } from '@/components/ui/button';

interface DocumentoIdentidadRevisionProps {
  datosIniciales: ClienteInput;
  extraccionAutomatica?: boolean;
  onConfirmar: (datos: ClienteInput) => void;
  onVolverEscaneo: () => void;
  isSaving?: boolean;
  modo: 'cliente' | 'abogado';
  confirmLabel?: string;
}

export function DocumentoIdentidadRevision({
  datosIniciales,
  extraccionAutomatica = false,
  onConfirmar,
  onVolverEscaneo,
  isSaving,
  modo,
  confirmLabel,
}: DocumentoIdentidadRevisionProps) {
  const esCliente = modo === 'cliente';

  return (
    <div className="space-y-4">
      {extraccionAutomatica ? (
        <div className="rounded-lg border border-border bg-card p-4 text-sm text-muted-foreground">
          {esCliente
            ? 'Revise que los datos extraídos de su documento son correctos. Puede ajustarlos antes de continuar al siguiente paso.'
            : 'Revise los datos extraídos del documento. Corrija lo necesario antes de crear el cliente.'}
        </div>
      ) : (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
          No se pudieron leer los datos automáticamente del documento. Para DNI/NIE, asegúrese de que el{' '}
          <strong>reverso</strong> (zona MRZ) sea legible. Complete el formulario manualmente.
        </div>
      )}
      <ClienteDatosForm
        initialValues={datosIniciales}
        onSubmit={onConfirmar}
        isSaving={isSaving}
        submitLabel={confirmLabel ?? (esCliente ? 'Confirmar y continuar' : 'Crear cliente')}
      />
      <Button type="button" variant="outline" onClick={onVolverEscaneo}>
        Volver al escaneo del documento
      </Button>
    </div>
  );
}
