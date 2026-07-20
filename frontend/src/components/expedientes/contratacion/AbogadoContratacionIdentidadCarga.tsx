import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ChevronDown, Upload } from 'lucide-react';
import { api, type ClienteInput } from '@/api/client';
import { DocumentoIdentidadFlujo } from '@/components/documento-identidad/DocumentoIdentidadFlujo';
import { DocumentoIdentidadRevision } from '@/components/documento-identidad/DocumentoIdentidadRevision';
import type { DocumentoIdentidadArchivos } from '@/components/documento-identidad/types';
import { Button } from '@/components/ui/button';
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

  const guardarMutation = useMutation({
    mutationFn: (datos: ClienteInput) => {
      if (!archivos) {
        throw new Error('Debe adjuntar el documento de identidad.');
      }
      return api.subirDocumentoIdentidadContratacion(expedienteId, {
        tipoEscaneo: archivos.tipoEscaneo,
        anverso: archivos.anverso,
        reverso: archivos.reverso,
        datos,
      });
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['contratacion', expedienteId] });
      void queryClient.invalidateQueries({ queryKey: ['cliente'] });
      setPaso('cerrado');
      setArchivos(null);
      setDatosIniciales(null);
      onGuardado?.();
    },
  });

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
              Suba las imágenes directamente desde su equipo (el cliente solo puede usar la cámara del portal).
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
              onConfirmar={(datos) => guardarMutation.mutate(datos)}
              onVolverEscaneo={() => {
                setArchivos(null);
                setDatosIniciales(null);
                setPaso('documento');
              }}
              isSaving={guardarMutation.isPending}
              confirmLabel="Guardar documento del cliente"
              volverLabel="Volver al escaneo"
            />
          )}

          {guardarMutation.isError && (
            <p className="text-sm text-destructive">{(guardarMutation.error as Error).message}</p>
          )}

          {paso === 'documento' && (
            <Button type="button" variant="ghost" size="sm" onClick={() => setPaso('cerrado')}>
              Cancelar
            </Button>
          )}
        </div>
      )}
    </section>
  );
}
