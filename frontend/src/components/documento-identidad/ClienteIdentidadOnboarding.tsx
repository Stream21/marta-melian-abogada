import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import type {
  AccesoExpedienteResponse,
  AccesoIdentidadEdicionResponse,
  ClienteInput,
  TipoEscaneoDocumentoIdentidad,
} from '@/api/client';
import { api, isClienteDuplicadoError } from '@/api/client';
import { ClienteDuplicadoConfirmDialog } from '@/components/cliente/ClienteDuplicadoConfirmDialog';
import {
  datosExtraidosAClienteInput,
  fusionarClienteInput,
  inferirCamposMrz,
  camposMrzConDocumentoExistente,
  type CampoMrz,
} from '@/lib/cliente-datos';
import {
  analizarDevolucionIdentidad,
  type LadoDocumentoDevolucion,
} from '@/lib/campos-devolucion';
import { ClienteIdentidadCorreccion } from './ClienteIdentidadCorreccion';
import { ClienteIdentidadEleccion } from './ClienteIdentidadEleccion';
import { DocumentoIdentidadFlujo } from './DocumentoIdentidadFlujo';
import { DocumentoIdentidadRevision } from './DocumentoIdentidadRevision';
import type { DocumentoIdentidadArchivos } from './types';

type Paso = 'eleccion' | 'correccion' | 'documento' | 'revision';

interface InicioRapidoDoc {
  tipoEscaneo: TipoEscaneoDocumentoIdentidad;
  ladoInicial?: 'anverso' | 'reverso';
  conservarLado?: 'anverso' | 'reverso';
  anversoUrl?: string | null;
  reversoUrl?: string | null;
}

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

function buildInicioRapido(
  identidadEdicion: AccesoIdentidadEdicionResponse | null | undefined,
  lado?: LadoDocumentoDevolucion,
): InicioRapidoDoc | null {
  if (!lado) return null;
  const tipo = (identidadEdicion?.tipoEscaneo ?? 'dni_nie') as TipoEscaneoDocumentoIdentidad;
  const base: InicioRapidoDoc = {
    tipoEscaneo: tipo,
    anversoUrl: identidadEdicion?.anversoUrl,
    reversoUrl: identidadEdicion?.reversoUrl,
  };
  if (lado === 'reverso') {
    return { ...base, ladoInicial: 'reverso', conservarLado: 'anverso' };
  }
  if (lado === 'anverso') {
    return { ...base, ladoInicial: 'anverso', conservarLado: 'reverso' };
  }
  return { ...base, ladoInicial: 'anverso' };
}

function estadoInicialCorreccion(
  identidadEdicion: AccesoIdentidadEdicionResponse | null | undefined,
  datosClienteEditables: ClienteInput | null | undefined,
): {
  paso: Paso;
  soloDatos: boolean;
  datosIniciales: ClienteInput | null;
  camposMrzBloqueados: CampoMrz[];
  inicioRapido: InicioRapidoDoc | null;
} {
  const analisis = analizarDevolucionIdentidad(identidadEdicion?.motivosDevolucion);
  const datos = datosClienteEditables ?? null;

  // Solo fotos: ir directo al escaneo (no al formulario).
  if (analisis.necesitaDocumento && !analisis.necesitaDatos && analisis.ladoDocumento) {
    return {
      paso: 'documento',
      soloDatos: false,
      datosIniciales: null,
      camposMrzBloqueados: [],
      inicioRapido: buildInicioRapido(identidadEdicion, analisis.ladoDocumento),
    };
  }

  // Solo datos: ir directo al formulario resaltado.
  if (analisis.necesitaDatos && !analisis.necesitaDocumento && datos) {
    return {
      paso: 'revision',
      soloDatos: true,
      datosIniciales: datos,
      camposMrzBloqueados: camposMrzConDocumentoExistente(datos),
      inicioRapido: null,
    };
  }

  // Ambos o motivos ambiguos: hub de elección.
  return {
    paso: 'correccion',
    soloDatos: false,
    datosIniciales: null,
    camposMrzBloqueados: [],
    inicioRapido: null,
  };
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
  const analisis = analizarDevolucionIdentidad(identidadEdicion?.motivosDevolucion);
  const camposResaltados = analisis.campos;

  const inicial = modoCorreccion
    ? estadoInicialCorreccion(identidadEdicion, datosClienteEditables)
    : null;

  const [paso, setPaso] = useState<Paso>(
    () => inicial?.paso ?? (puedeReutilizar ? 'eleccion' : 'documento'),
  );
  const [archivos, setArchivos] = useState<DocumentoIdentidadArchivos | null>(null);
  const [datosIniciales, setDatosIniciales] = useState<ClienteInput | null>(
    () => inicial?.datosIniciales ?? null,
  );
  const [extraccionAutomatica, setExtraccionAutomatica] = useState(false);
  const [soloDatos, setSoloDatos] = useState(() => inicial?.soloDatos ?? false);
  const [camposMrzBloqueados, setCamposMrzBloqueados] = useState<CampoMrz[]>(
    () => inicial?.camposMrzBloqueados ?? [],
  );
  const [inicioRapido, setInicioRapido] = useState<InicioRapidoDoc | null>(
    () => inicial?.inicioRapido ?? null,
  );
  const [duplicado, setDuplicado] = useState<{
    archivos: DocumentoIdentidadArchivos | null;
    datos: ClienteInput;
    soloDatos: boolean;
    nombre: string;
    campo?: string;
  } | null>(null);

  const guardarAccesoMutation = useMutation({
    mutationFn: (payload: {
      archivos: DocumentoIdentidadArchivos | null;
      datos: ClienteInput;
      soloDatos: boolean;
      permitirDuplicado?: boolean;
    }) =>
      api.guardarDatosIdentidadAcceso(token!, {
        tipoEscaneo: (payload.archivos?.tipoEscaneo
          ?? identidadEdicion?.tipoEscaneo
          ?? 'dni_nie') as TipoEscaneoDocumentoIdentidad,
        anverso: payload.archivos?.anverso ?? null,
        reverso: payload.archivos?.reverso ?? null,
        datos: payload.datos,
        soloDatos: payload.soloDatos,
        permitirDuplicado: payload.permitirDuplicado,
      }),
    onSuccess: (data) => {
      setDuplicado(null);
      onCompletado?.(data);
    },
    onError: (error, variables) => {
      if (isClienteDuplicadoError(error) && !variables.permitirDuplicado) {
        setDuplicado({
          archivos: variables.archivos,
          datos: variables.datos,
          soloDatos: variables.soloDatos,
          nombre: error.clienteExistenteNombre ?? 'otro cliente',
          campo: error.campoDuplicado,
        });
      }
    },
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

  const irAActualizarDocumento = (lado?: LadoDocumentoDevolucion) => {
    setSoloDatos(false);
    setArchivos(null);
    setDatosIniciales(null);
    setInicioRapido(buildInicioRapido(identidadEdicion, lado ?? 'completo'));
    setPaso('documento');
  };

  const volverDesdeDocumento = () => {
    setArchivos(null);
    setDatosIniciales(null);
    setSoloDatos(false);
    setInicioRapido(null);
    if (modoCorreccion) {
      // Si solo pedían documento, al volver mostrar el hub por si quieren otra opción.
      setPaso('correccion');
      return;
    }
    setPaso(puedeReutilizar ? 'eleccion' : 'documento');
  };

  const volverDesdeRevision = () => {
    if (soloDatos && modoCorreccion) {
      setSoloDatos(false);
      setDatosIniciales(null);
      // Si también pedían foto, ofrecer escaneo; si no, hub.
      if (analisis.necesitaDocumento && analisis.ladoDocumento) {
        irAActualizarDocumento(analisis.ladoDocumento);
        return;
      }
      setPaso('correccion');
      return;
    }
    if (archivos) {
      setPaso('documento');
      return;
    }
    volverDesdeDocumento();
  };

  const etiquetaVolverRevision = (() => {
    if (soloDatos && modoCorreccion && analisis.necesitaDocumento) {
      return 'Ir a fotografiar el documento';
    }
    if (soloDatos) return 'Volver a opciones de corrección';
    if (archivos) return 'Volver a las fotos';
    if (puedeReutilizar) return 'Volver a la elección del documento';
    return 'Volver al escaneo del documento';
  })();

  return (
    <div className="space-y-6">
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
        <>
          {modoCorreccion && analisis.necesitaDocumento && (
            <div className="rounded-xl border border-primary/25 bg-primary/5 px-4 py-3 text-sm">
              <p className="font-semibold text-foreground">
                {analisis.ladoDocumento === 'anverso'
                  ? 'Fotografie de nuevo el anverso'
                  : analisis.ladoDocumento === 'reverso'
                    ? 'Fotografie de nuevo el reverso'
                    : 'Vuelva a escanear su documento'}
              </p>
              <p className="mt-1 text-xs text-muted-foreground">
                {notaDevolucion?.trim()
                  ? notaDevolucion
                  : 'Siga las indicaciones en pantalla. Cuando termine, revisará los datos antes de enviar.'}
              </p>
            </div>
          )}
          <DocumentoIdentidadFlujo
            key={
              inicioRapido
                ? `doc-${inicioRapido.ladoInicial ?? 'a'}-${inicioRapido.conservarLado ?? 'n'}`
                : archivos
                  ? `doc-${archivos.anverso.name}-${archivos.anverso.size}`
                  : 'doc-nuevo'
            }
            modo="cliente"
            tipoServicio={tipoServicio}
            inicioRapido={inicioRapido ?? undefined}
            capturasPrevias={archivos}
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
        </>
      )}

      {paso === 'revision' && datosIniciales && (soloDatos || archivos) && (
        <DocumentoIdentidadRevision
          modo="cliente"
          tipoServicio={tipoServicio}
          extraccionAutomatica={extraccionAutomatica}
          camposSoloLectura={camposMrzBloqueados}
          camposResaltados={soloDatos || modoCorreccion ? camposResaltados : []}
          datosIniciales={datosIniciales}
          onConfirmar={handleConfirmar}
          onVolverEscaneo={volverDesdeRevision}
          isSaving={isSaving}
          confirmLabel="Confirmar y continuar"
          volverLabel={etiquetaVolverRevision}
        />
      )}

      {guardarAccesoMutation.isError && !isClienteDuplicadoError(guardarAccesoMutation.error) && (
        <p className="text-sm text-destructive">{(guardarAccesoMutation.error as Error).message}</p>
      )}
      {reutilizarMutation.isError && (
        <p className="text-sm text-destructive">{(reutilizarMutation.error as Error).message}</p>
      )}

      <ClienteDuplicadoConfirmDialog
        open={!!duplicado}
        clienteNombre={duplicado?.nombre ?? ''}
        campo={duplicado?.campo}
        loading={guardarAccesoMutation.isPending}
        onCancel={() => setDuplicado(null)}
        onConfirm={() => {
          if (!duplicado) return;
          guardarAccesoMutation.mutate({ ...duplicado, permitirDuplicado: true });
        }}
        confirmLabel="Continuar de todos modos"
      />
    </div>
  );
}
