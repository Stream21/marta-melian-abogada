import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import type {
  AccesoExpedienteResponse,
  AccesoIdentidadEdicionResponse,
  ClienteInput,
  TipoEscaneoDocumentoIdentidad,
} from '@/api/client';
import { api } from '@/api/client';
import { datosExtraidosAClienteInput, fusionarClienteInput, inferirCamposMrz, camposMrzConDocumentoExistente, type CampoMrz } from '@/lib/cliente-datos';
import { ClienteIdentidadCorreccion } from './ClienteIdentidadCorreccion';
import { ClienteIdentidadEleccion } from './ClienteIdentidadEleccion';
import { DocumentoIdentidadFlujo } from './DocumentoIdentidadFlujo';
import { DocumentoIdentidadRevision } from './DocumentoIdentidadRevision';
import type { DocumentoIdentidadArchivos } from './types';

type Paso = 'eleccion' | 'correccion' | 'documento' | 'revision';

interface ClienteIdentidadOnboardingProps {
  token?: string;
  tipoServicio?: string | null;
  identidadEdicion?: AccesoIdentidadEdicionResponse | null;
  datosClienteEditables?: ClienteInput | null;
  notaDevolucion?: string | null;
  onConfirmar?: (payload: { archivos: DocumentoIdentidadArchivos; datos: ClienteInput }) => void;
  onCompletado?: (data: AccesoExpedienteResponse) => void;
  isSaving?: boolean;
}

export function ClienteIdentidadOnboarding({
  token,
  tipoServicio,
  identidadEdicion,
  datosClienteEditables,
  notaDevolucion,
  onConfirmar,
  onCompletado,
  isSaving: isSavingExternal,
}: ClienteIdentidadOnboardingProps) {
  const modoCorreccion = !!identidadEdicion?.modoCorreccion && !!datosClienteEditables;
  const puedeReutilizar =
    !!identidadEdicion?.tieneDocumentoPrevio && !!datosClienteEditables && !modoCorreccion;

  const [paso, setPaso] = useState<Paso>(
    modoCorreccion ? 'correccion' : puedeReutilizar ? 'eleccion' : 'documento',
  );
  const [archivos, setArchivos] = useState<DocumentoIdentidadArchivos | null>(null);
  const [datosIniciales, setDatosIniciales] = useState<ClienteInput | null>(null);
  const [extraccionAutomatica, setExtraccionAutomatica] = useState(false);
  const [soloDatos, setSoloDatos] = useState(false);
  const [camposMrzBloqueados, setCamposMrzBloqueados] = useState<CampoMrz[]>([]);
  const [inicioRapido, setInicioRapido] = useState<{
    tipoEscaneo: TipoEscaneoDocumentoIdentidad;
    ladoInicial?: 'anverso' | 'reverso';
    conservarLado?: 'anverso' | 'reverso';
    anversoUrl?: string | null;
    reversoUrl?: string | null;
  } | null>(null);

  const guardarAccesoMutation = useMutation({
    mutationFn: (payload: {
      archivos: DocumentoIdentidadArchivos | null;
      datos: ClienteInput;
      soloDatos: boolean;
    }) =>
      api.guardarDatosIdentidadAcceso(token!, {
        tipoEscaneo: payload.archivos?.tipoEscaneo ?? identidadEdicion?.tipoEscaneo ?? 'dni_nie',
        anverso: payload.archivos?.anverso ?? null,
        reverso: payload.archivos?.reverso ?? null,
        datos: payload.datos,
        soloDatos: payload.soloDatos,
      }),
    onSuccess: (data) => onCompletado?.(data),
  });

  const reutilizarMutation = useMutation({
    mutationFn: () => api.reutilizarDocumentoIdentidadAcceso(token!),
    onSuccess: (data) => onCompletado?.(data),
  });

  const isSaving =
    isSavingExternal ?? (guardarAccesoMutation.isPending || reutilizarMutation.isPending);

  const handleConfirmar = (datos: ClienteInput) => {
    if (token) {
      if (!soloDatos && !archivos) return;
      guardarAccesoMutation.mutate({ archivos: soloDatos ? null : archivos, datos, soloDatos });
      return;
    }

    if (!archivos) return;
    onConfirmar?.({ archivos, datos });
  };

  const irACorregirDatos = () => {
    setSoloDatos(true);
    setArchivos(null);
    setExtraccionAutomatica(false);
    const datos = datosClienteEditables ?? null;
    setDatosIniciales(datos);
    setCamposMrzBloqueados(datos ? camposMrzConDocumentoExistente(datos) : []);
    setPaso('revision');
  };

  const irAActualizarDocumento = (lado?: 'anverso' | 'reverso' | 'completo') => {
    setSoloDatos(false);
    setArchivos(null);
    setDatosIniciales(null);
    setInicioRapido(null);
    const tipo = (identidadEdicion?.tipoEscaneo ?? 'dni_nie') as TipoEscaneoDocumentoIdentidad;
    const base = {
      tipoEscaneo: tipo,
      anversoUrl: identidadEdicion?.anversoUrl,
      reversoUrl: identidadEdicion?.reversoUrl,
    };
    if (lado === 'reverso') {
      setInicioRapido({ ...base, ladoInicial: 'reverso', conservarLado: 'anverso' });
    } else if (lado === 'anverso') {
      setInicioRapido({ ...base, ladoInicial: 'anverso', conservarLado: 'reverso' });
    } else if (lado) {
      setInicioRapido({ ...base, ladoInicial: 'anverso' });
    }
    setPaso('documento');
  };

  const volverDesdeDocumento = () => {
    setArchivos(null);
    setDatosIniciales(null);
    setSoloDatos(false);
    setInicioRapido(null);
    setPaso(modoCorreccion ? 'correccion' : puedeReutilizar ? 'eleccion' : 'documento');
  };

  return (
    <div className="space-y-6">
      {paso === 'documento' && (
        <p className="rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-muted-foreground">
          Para verificar su identidad debe <strong className="text-foreground">fotografiar el documento con la cámara</strong>{' '}
          (escaneo guiado con OCR). No puede subir archivos desde la galería ni desde su equipo.
        </p>
      )}
      {paso === 'eleccion' && identidadEdicion && (
        <ClienteIdentidadEleccion
          identidadEdicion={identidadEdicion}
          onReutilizar={() => reutilizarMutation.mutate()}
          onEscanearNuevo={() => irAActualizarDocumento()}
          reutilizando={reutilizarMutation.isPending}
        />
      )}

      {paso === 'correccion' && identidadEdicion && (
        <ClienteIdentidadCorreccion
          identidadEdicion={identidadEdicion}
          notaDevolucion={notaDevolucion}
          tipoServicio={tipoServicio}
          onCorregirDatos={irACorregirDatos}
          onActualizarDocumento={irAActualizarDocumento}
        />
      )}

      {paso === 'documento' && (
        <DocumentoIdentidadFlujo
          modo="cliente"
          tipoServicio={tipoServicio}
          inicioRapido={inicioRapido ?? undefined}
          onReiniciarTipo={() => setInicioRapido(null)}
          onVolver={
            modoCorreccion || puedeReutilizar || inicioRapido
              ? volverDesdeDocumento
              : undefined
          }
          extraerDocumento={
            token ? (input) => api.extraerDocumentoIdentidadAcceso(token, input) : undefined
          }
          onCompletado={({ archivos: files, datosExtraidos }) => {
            setArchivos(files);
            setSoloDatos(false);
            setExtraccionAutomatica(datosExtraidos.extraccionAutomatica === true);
            setCamposMrzBloqueados(inferirCamposMrz(datosExtraidos));
            const extraidos = datosExtraidosAClienteInput(datosExtraidos);
            setDatosIniciales(
              datosClienteEditables
                ? fusionarClienteInput(datosClienteEditables, extraidos)
                : extraidos,
            );
            setPaso('revision');
          }}
        />
      )}

      {paso === 'revision' && datosIniciales && (soloDatos || archivos) && (
        <DocumentoIdentidadRevision
          modo="cliente"
          tipoServicio={tipoServicio}
          extraccionAutomatica={extraccionAutomatica}
          camposSoloLectura={camposMrzBloqueados}
          datosIniciales={datosIniciales}
          onConfirmar={handleConfirmar}
          onVolverEscaneo={() => {
            if (soloDatos && modoCorreccion) {
              setSoloDatos(false);
              setDatosIniciales(null);
              setPaso('correccion');
              return;
            }
            volverDesdeDocumento();
          }}
          isSaving={isSaving}
          confirmLabel="Confirmar y continuar"
          volverLabel={
            soloDatos
              ? 'Volver a opciones de corrección'
              : puedeReutilizar
                ? 'Volver a la elección del documento'
                : 'Volver al escaneo del documento'
          }
        />
      )}

      {guardarAccesoMutation.isError && (
        <p className="text-sm text-destructive">{(guardarAccesoMutation.error as Error).message}</p>
      )}
      {reutilizarMutation.isError && (
        <p className="text-sm text-destructive">{(reutilizarMutation.error as Error).message}</p>
      )}
    </div>
  );
}
