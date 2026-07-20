import { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { FileDown, FilePlus2, FileText, Pencil } from 'lucide-react';
import { api, openAuthenticatedDocument, type ExpedienteEscritoListItem } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ExpedienteEscritoEditor } from './ExpedienteEscritoEditor';
import { cn } from '@/lib/utils';

interface ExpedienteEscritosPanelProps {
  expedienteId: string;
}

type Vista = 'lista' | 'nuevo' | 'editar';

function formatFecha(iso: string) {
  return new Date(iso).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function ExpedienteEscritosPanel({ expedienteId }: ExpedienteEscritosPanelProps) {
  const queryClient = useQueryClient();
  const [vista, setVista] = useState<Vista>('lista');
  const [escritoSeleccionado, setEscritoSeleccionado] = useState<ExpedienteEscritoListItem | null>(null);
  const [contenidoEdit, setContenidoEdit] = useState<{ titulo: string; html: string } | null>(null);
  const [abriendoPdfId, setAbriendoPdfId] = useState<string | null>(null);

  const { data: escritos = [], isLoading } = useQuery({
    queryKey: ['escritos', expedienteId],
    queryFn: () => api.getEscritosExpediente(expedienteId),
  });

  const { isFetching: cargandoDetalle } = useQuery({
    queryKey: ['escrito', expedienteId, escritoSeleccionado?.id],
    queryFn: async () => {
      if (!escritoSeleccionado) return null;
      const detalle = await api.getEscritoExpediente(expedienteId, escritoSeleccionado.id);
      setContenidoEdit({ titulo: detalle.titulo, html: detalle.contenidoHtml });
      return detalle;
    },
    enabled: vista === 'editar' && escritoSeleccionado !== null,
  });

  const refrescar = () => {
    void queryClient.invalidateQueries({ queryKey: ['escritos', expedienteId] });
    void queryClient.invalidateQueries({ queryKey: ['expediente-documentacion', expedienteId] });
  };

  const abrirNuevo = () => {
    setEscritoSeleccionado(null);
    setContenidoEdit(null);
    setVista('nuevo');
  };

  const abrirEditar = (escrito: ExpedienteEscritoListItem) => {
    setEscritoSeleccionado(escrito);
    setContenidoEdit(null);
    setVista('editar');
  };

  const volverLista = () => {
    setVista('lista');
    setEscritoSeleccionado(null);
    setContenidoEdit(null);
  };

  const onSaved = () => {
    refrescar();
    volverLista();
  };

  const abrirPdf = async (escritoId: string) => {
    setAbriendoPdfId(escritoId);
    try {
      await openAuthenticatedDocument(api.escritoExpedientePdfUrl(expedienteId, escritoId));
    } catch (e) {
      window.alert(e instanceof Error ? e.message : 'No se pudo abrir el PDF.');
    } finally {
      setAbriendoPdfId(null);
    }
  };

  return (
    <div className="grid gap-6 lg:grid-cols-[minmax(260px,320px)_1fr]">
      <aside className="panel flex flex-col">
        <div className="panel-header border-b">
          <div className="panel-header-icon">
            <FileText className="h-5 w-5" />
          </div>
          <div className="min-w-0 flex-1">
            <h2 className="panel-title">Escritos del expediente</h2>
            <p className="text-xs text-muted-foreground mt-0.5">
              Redacte, edite y descargue PDFs con sello del abogado.
            </p>
          </div>
        </div>

        <div className="p-4 border-b">
          <Button className="w-full" size="sm" onClick={abrirNuevo}>
            <FilePlus2 className="mr-2 h-4 w-4" />
            Nuevo escrito
          </Button>
        </div>

        <div className="flex-1 overflow-y-auto p-3 space-y-2">
          {isLoading ? (
            <p className="text-sm text-muted-foreground text-center py-6">Cargando…</p>
          ) : escritos.length === 0 ? (
            <p className="text-sm text-muted-foreground text-center py-6 italic">
              Aún no hay escritos. Cree el primero con el botón superior.
            </p>
          ) : (
            escritos.map((escrito) => (
              <div
                key={escrito.id}
                className={cn(
                  'rounded-lg border p-3 transition-colors',
                  escritoSeleccionado?.id === escrito.id && vista === 'editar'
                    ? 'border-primary/40 bg-primary/5'
                    : 'hover:bg-muted/40',
                )}
              >
                <p className="font-medium text-sm leading-snug">{escrito.titulo}</p>
                <p className="text-xs text-muted-foreground mt-1">{formatFecha(escrito.createdAt)}</p>
                <div className="mt-3 flex flex-wrap gap-2">
                  <Button variant="outline" size="sm" onClick={() => abrirEditar(escrito)}>
                    <Pencil className="mr-1.5 h-3.5 w-3.5" />
                    Editar
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    disabled={abriendoPdfId === escrito.id}
                    onClick={() => void abrirPdf(escrito.id)}
                  >
                    <FileDown className="mr-1.5 h-3.5 w-3.5" />
                    {abriendoPdfId === escrito.id ? 'Abriendo…' : 'PDF'}
                  </Button>
                </div>
              </div>
            ))
          )}
        </div>
      </aside>

      <section className="panel p-6">
        {vista === 'lista' && (
          <div className="flex flex-col items-center justify-center py-16 text-center">
            <div className="mb-4 rounded-full bg-muted p-4">
              <FileText className="h-8 w-8 text-muted-foreground" />
            </div>
            <h3 className="text-lg font-semibold">Seleccione o cree un escrito</h3>
            <p className="mt-2 max-w-md text-sm text-muted-foreground">
              Use la lista de la izquierda para editar un escrito existente o pulse «Nuevo escrito» para redactar
              uno con variables del cliente y sello del despacho en el PDF.
            </p>
            <Button className="mt-6" onClick={abrirNuevo}>
              <FilePlus2 className="mr-2 h-4 w-4" />
              Nuevo escrito
            </Button>
          </div>
        )}

        {vista === 'nuevo' && (
          <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-2">
              <Badge variant="info">Nuevo</Badge>
              <h3 className="text-lg font-semibold">Redactar escrito</h3>
            </div>
            <ExpedienteEscritoEditor expedienteId={expedienteId} onSaved={onSaved} onCancel={volverLista} />
          </div>
        )}

        {vista === 'editar' && escritoSeleccionado && (
          <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-2">
              <Badge variant="secondary">Editando</Badge>
              <h3 className="text-lg font-semibold">{escritoSeleccionado.titulo}</h3>
            </div>
            {cargandoDetalle || !contenidoEdit ? (
              <p className="text-sm text-muted-foreground py-8 text-center">Cargando contenido…</p>
            ) : (
              <ExpedienteEscritoEditor
                expedienteId={expedienteId}
                escritoId={escritoSeleccionado.id}
                initialTitulo={contenidoEdit.titulo}
                initialContenidoHtml={contenidoEdit.html}
                onSaved={onSaved}
                onCancel={volverLista}
              />
            )}
          </div>
        )}
      </section>
    </div>
  );
}
