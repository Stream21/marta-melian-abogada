import { useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useMutation } from '@tanstack/react-query';
import { ArrowLeft } from 'lucide-react';
import { api, type ClienteInput } from '@/api/client';
import { DocumentoIdentidadFlujo } from '@/components/documento-identidad/DocumentoIdentidadFlujo';
import { DocumentoIdentidadRevision } from '@/components/documento-identidad/DocumentoIdentidadRevision';
import type { DocumentoIdentidadArchivos } from '@/components/documento-identidad/types';
import { datosExtraidosAClienteInput } from '@/lib/cliente-datos';
import { Button } from '@/components/ui/button';

type Paso = 'documento' | 'revision';

export function ClienteNuevoPage() {
  const navigate = useNavigate();
  const [paso, setPaso] = useState<Paso>('documento');
  const [archivos, setArchivos] = useState<DocumentoIdentidadArchivos | null>(null);
  const [datosIniciales, setDatosIniciales] = useState<ClienteInput | null>(null);
  const [extraccionAutomatica, setExtraccionAutomatica] = useState(false);

  const createMutation = useMutation({
    mutationFn: (body: ClienteInput) => {
      if (!archivos) {
        throw new Error('Debe aportar el documento de identidad antes de crear el cliente.');
      }
      return api.postClienteConDocumento({
        tipoEscaneo: archivos.tipoEscaneo,
        anverso: archivos.anverso,
        reverso: archivos.reverso,
        datos: body,
      });
    },
    onSuccess: (cliente) => {
      void navigate({ to: '/clientes/$clienteId', params: { clienteId: cliente.id } });
    },
  });

  return (
    <div className="flex min-h-full flex-col bg-muted/30">
      <main className="flex-1 p-6 md:p-8">
        <div className="mx-auto flex max-w-[900px] flex-col gap-6">
          <Button variant="ghost" size="sm" className="w-fit -ml-2" asChild>
            <Link to="/clientes">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Clientes
            </Link>
          </Button>
          <div>
            <h1 className="page-title">Nuevo cliente</h1>
            <p className="page-subtitle">
              Mismo proceso que seguirá el cliente: tipo de documento, captura, extracción de datos y
              revisión. Aquí puede subir un pantallazo en lugar de usar la cámara.
            </p>
          </div>

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
              onConfirmar={(body) => createMutation.mutate(body)}
              onVolverEscaneo={() => {
                setArchivos(null);
                setDatosIniciales(null);
                setPaso('documento');
              }}
              isSaving={createMutation.isPending}
            />
          )}

          {createMutation.isError && (
            <p className="text-sm text-destructive">{(createMutation.error as Error).message}</p>
          )}
        </div>
      </main>
    </div>
  );
}
