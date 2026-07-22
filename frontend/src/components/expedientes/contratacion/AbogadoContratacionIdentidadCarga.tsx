import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ChevronDown, Upload } from 'lucide-react';
import { api, isClienteDuplicadoError, type ClienteInput } from '@/api/client';
import { ClienteDuplicadoConfirmDialog } from '@/components/cliente/ClienteDuplicadoConfirmDialog';
import { DocumentoIdentidadFlujo } from '@/components/documento-identidad/DocumentoIdentidadFlujo';
import { DocumentoIdentidadRevision } from '@/components/documento-identidad/DocumentoIdentidadRevision';
import type { DocumentoIdentidadArchivos } from '@/components/documento-identidad/types';
import { datosExtraidosAClienteInput } from '@/lib/cliente-datos';
import { cn } from '@/lib/utils';

type Paso = 'cerrado' | 'documento' | 'revision';

interface AbogadoContratacionIdentidadCargaProps {
  expedienteId: string;
  onGuardado?: () => void;
}

export function AbogadoContratacionIdentidadCarga({
  expedienteId,
  onGuardado,
}: AbogadoContratacionIdentidadCargaProps) {
  const queryClient = useQueryClient();
  const [paso, setPaso] = useState<Paso>('cerrado');
  const [archivos, setArchivos] = useState<DocumentoIdentidadArchivos | null>(null);
  const [datosIniciales, setDatosIniciales] = useState<ClienteInput | null>(null);
  const [extraccionAutomatica, setExtraccionAutomatica] = useState(false);
  const [duplicado, setDuplicado] = useState<{
    datos: ClienteInput;
    nombre: string;
    campo?: string;
  } | null>(null);

  const guardarMutation = useMutation({
    mutationFn: ({
      datos,
      permitirDuplicado = false,
    }: {
      datos: ClienteInput;
      permitirDuplicado?: boolean;
    }) => {
      if (!archivos) {
        throw new Error('Debe adjuntar el documento de identidad.');
      }
      return api.subirDocumentoIdentidadContratacion(expedienteId, {
        tipoEscaneo: archivos.tipoEscaneo,
        anverso: archivos.anverso,
        reverso: archivos.reverso,
        datos,
        permitirDuplicado,
      });
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['contratacion', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['cliente'] });
      setDuplicado(null);
      setPaso('cerrado');
      setArchivos(null);
      setDatosIniciales(null);
      onGuardado?.();
    },
    onError: (error, variables) => {
      if (isClienteDuplicadoError(error) && !variables.permitirDuplicado) {
        setDuplicado({
          datos: variables.datos,
          nombre: error.clienteExistenteNombre ?? 'otro cliente',
          campo: error.campoDuplicado,
        });
      }
    },
  });

  const errorSinDuplicado =
    guardarMutation.isError && !isClienteDuplicadoError(guardarMutation.error)
      ? (guardarMutation.error as Error).message
      : null;

  return (
    <section className="rounded-xl border border-primary/20 bg-primary/5 overflow-hidden">
      <button
        type="button"
        className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
        onClick={() => setPaso((p) => (p === 'cerrado' ? 'documento' : 'cerrado'))}
      >
        <div className="flex items-center gap-2">
          <Upload className="h-4 w-4 text-primary" />
          <div>
            <p className="text-sm font-semibold text-foreground">Cargar documento en nombre del cliente</p>
            <p className="text-xs text-muted-foreground">
              Seleccione DNI/NIE o pasaporte y suba las imágenes JPG/PNG desde su equipo.
            </p>
          </div>
        </div>
        <ChevronDown className={cn('h-4 w-4 shrink-0 transition-transform', paso !== 'cerrado' && 'rotate-180')} />
      </button>

      {paso !== 'cerrado' && (
        <div className="space-y-4 border-t border-primary/15 bg-card p-4">
          {paso === 'documento' && (
            <DocumentoIdentidadFlujo
              modo="abogado"
              ocultarIndicadorPasos
              onCompletado={({ archivos: files, datosExtraidos }) => {
                setArchivos(files);
                setExtraccionAutomatica(datosExtraidos.extraccionAutomatica === true);
                setDatosIniciales(datosExtraidosAClienteInput(datosExtraidos));
                setPaso('revision');
              }}
            />
          )}

          {paso === 'revision' && datosIniciales && (
            <DocumentoIdentidadRevision
              modo="abogado"
              extraccionAutomatica={extraccionAutomatica}
              datosIniciales={datosIniciales}
              onConfirmar={(datos) => guardarMutation.mutate({ datos })}
              onVolverEscaneo={() => {
                setArchivos(null);
                setDatosIniciales(null);
                setDuplicado(null);
                setPaso('documento');
              }}
              isSaving={guardarMutation.isPending}
              confirmLabel="Guardar documento del cliente"
              volverLabel="Volver a subir imágenes"
            />
          )}

          {errorSinDuplicado && <p className="text-sm text-destructive">{errorSinDuplicado}</p>}
        </div>
      )}

      <ClienteDuplicadoConfirmDialog
        open={!!duplicado}
        clienteNombre={duplicado?.nombre ?? ''}
        campo={duplicado?.campo}
        loading={guardarMutation.isPending}
        onCancel={() => setDuplicado(null)}
        onConfirm={() => {
          if (!duplicado) return;
          guardarMutation.mutate({ datos: duplicado.datos, permitirDuplicado: true });
        }}
        confirmLabel="Continuar con este cliente"
      />
    </section>
  );
}
