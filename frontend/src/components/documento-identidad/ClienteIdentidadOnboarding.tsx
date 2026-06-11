import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import type { AccesoExpedienteResponse, ClienteInput } from '@/api/client';
import { api } from '@/api/client';
import { datosExtraidosAClienteInput } from '@/lib/cliente-datos';
import { DocumentoIdentidadFlujo } from './DocumentoIdentidadFlujo';
import { DocumentoIdentidadRevision } from './DocumentoIdentidadRevision';
import type { DocumentoIdentidadArchivos } from './types';

type Paso = 'documento' | 'revision';

interface ClienteIdentidadOnboardingProps {
  /** Token del portal público. Si se indica, usa la API de acceso sin autenticación. */
  token?: string;
  /** Callback tras guardar (modo abogado u otros flujos sin token). */
  onConfirmar?: (payload: { archivos: DocumentoIdentidadArchivos; datos: ClienteInput }) => void;
  /** Callback tras guardar en el portal (devuelve estado actualizado del expediente). */
  onCompletado?: (data: AccesoExpedienteResponse) => void;
  isSaving?: boolean;
}

/**
 * Flujo completo de identidad para el portal del cliente:
 * 1. Selección de documento → cámara → extracción
 * 2. Revisión y ajuste de datos → continuar al siguiente paso
 */
export function ClienteIdentidadOnboarding({
  token,
  onConfirmar,
  onCompletado,
  isSaving: isSavingExternal,
}: ClienteIdentidadOnboardingProps) {
  const [paso, setPaso] = useState<Paso>('documento');
  const [archivos, setArchivos] = useState<DocumentoIdentidadArchivos | null>(null);
  const [datosIniciales, setDatosIniciales] = useState<ClienteInput | null>(null);
  const [extraccionAutomatica, setExtraccionAutomatica] = useState(false);

  const guardarAccesoMutation = useMutation({
    mutationFn: (payload: { archivos: DocumentoIdentidadArchivos; datos: ClienteInput }) =>
      api.guardarDatosIdentidadAcceso(token!, {
        tipoEscaneo: payload.archivos.tipoEscaneo,
        anverso: payload.archivos.anverso,
        reverso: payload.archivos.reverso,
        datos: payload.datos,
      }),
    onSuccess: (data) => onCompletado?.(data),
  });

  const isSaving = isSavingExternal ?? guardarAccesoMutation.isPending;

  const handleConfirmar = (datos: ClienteInput) => {
    if (!archivos) return;

    if (token) {
      guardarAccesoMutation.mutate({ archivos, datos });
      return;
    }

    onConfirmar?.({ archivos, datos });
  };

  return (
    <div className="space-y-6">
      {paso === 'documento' && (
        <DocumentoIdentidadFlujo
          modo="cliente"
          extraerDocumento={
            token
              ? (input) => api.extraerDocumentoIdentidadAcceso(token, input)
              : undefined
          }
          onCompletado={({ archivos: files, datosExtraidos }) => {
            setArchivos(files);
            setExtraccionAutomatica(datosExtraidos.extraccionAutomatica === true);
            setDatosIniciales(datosExtraidosAClienteInput(datosExtraidos));
            setPaso('revision');
          }}
        />
      )}

      {paso === 'revision' && datosIniciales && archivos && (
        <DocumentoIdentidadRevision
          modo="cliente"
          extraccionAutomatica={extraccionAutomatica}
          datosIniciales={datosIniciales}
          onConfirmar={handleConfirmar}
          onVolverEscaneo={() => {
            setArchivos(null);
            setDatosIniciales(null);
            setPaso('documento');
          }}
          isSaving={isSaving}
          confirmLabel="Confirmar y continuar"
        />
      )}

      {guardarAccesoMutation.isError && (
        <p className="text-sm text-destructive">{(guardarAccesoMutation.error as Error).message}</p>
      )}
    </div>
  );
}
