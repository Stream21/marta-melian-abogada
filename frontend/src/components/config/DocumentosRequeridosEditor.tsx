import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { FileText, Pencil, Plus, Trash2 } from 'lucide-react';
import { api, FASE_DOCUMENTOS_CLIENTE, type DocumentoRequerido, type FaseExpediente } from '@/api/client';
import {
  DocumentoRequeridoFormModal,
  type DocumentoRequeridoFormValues,
} from '@/components/config/tramite/DocumentoRequeridoFormModal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

export type DocumentosRequeridosScope = 'tramite' | 'servicio';

interface DocumentosRequeridosEditorProps {
  scope: DocumentosRequeridosScope;
  entityId: string;
  title?: string;
  subtitle?: string;
  readOnly?: boolean;
}

function normalizeFase(fase: unknown): FaseExpediente {
  if (fase === 'apertura') return FASE_DOCUMENTOS_CLIENTE;
  if (fase === 'resolucion') return 4;
  if (typeof fase === 'number' && fase >= 1 && fase <= 4) return fase as FaseExpediente;
  return FASE_DOCUMENTOS_CLIENTE;
}

function normalizeDocumento(doc: DocumentoRequerido, index: number): DocumentoRequerido {
  const tipo = doc.tipo ?? 'individual';
  return {
    ...doc,
    fase: normalizeFase(doc.fase),
    tipo,
    maxImagenes: tipo === 'individual' ? 1 : (doc.maxImagenes ?? 2),
    orden: index,
  };
}

function capturaLabel(doc: DocumentoRequerido): string | null {
  if (doc.tipo === 'conjunto' && doc.maxImagenes > 1) {
    return `Hasta ${doc.maxImagenes} archivos`;
  }
  return null;
}

function toPayload(documentos: DocumentoRequerido[]) {
  return documentos.map(({ id, nombre, descripcion, obligatorio, tipo, maxImagenes }, index) => ({
    id,
    fase: FASE_DOCUMENTOS_CLIENTE,
    nombre,
    descripcion,
    obligatorio,
    tipo,
    maxImagenes: tipo === 'individual' ? 1 : maxImagenes,
    orden: index,
  }));
}

function queryKey(scope: DocumentosRequeridosScope, entityId: string) {
  return scope === 'servicio'
    ? ['documentos-requeridos-servicio', entityId]
    : ['documentos-requeridos', entityId];
}

export function DocumentosRequeridosEditor({
  scope,
  entityId,
  title = 'Documentación requerida',
  subtitle,
  readOnly = false,
}: DocumentosRequeridosEditorProps) {
  const queryClient = useQueryClient();
  const [documentos, setDocumentos] = useState<DocumentoRequerido[]>([]);
  const [initialized, setInitialized] = useState(false);
  const [modalOpen, setModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'create' | 'edit'>('create');
  const [editingId, setEditingId] = useState<string | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<DocumentoRequerido | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: queryKey(scope, entityId),
    queryFn: () =>
      scope === 'servicio'
        ? api.getDocumentosRequeridosServicio(entityId)
        : api.getDocumentosRequeridos(entityId),
  });

  useEffect(() => {
    if (!data || initialized) return;
    setDocumentos(
      data.documentos
        .filter((d) => normalizeFase(d.fase) === FASE_DOCUMENTOS_CLIENTE)
        .map((d, index) => normalizeDocumento(d, index)),
    );
    setInitialized(true);
  }, [data, initialized]);

  const saveMutation = useMutation({
    mutationFn: (next: DocumentoRequerido[]) =>
      scope === 'servicio'
        ? api.putDocumentosRequeridosServicio(entityId, toPayload(next))
        : api.putDocumentosRequeridos(entityId, toPayload(next)),
    onSuccess: (response) => {
      setDocumentos(
        response.documentos
          .filter((d) => normalizeFase(d.fase) === FASE_DOCUMENTOS_CLIENTE)
          .map((d, index) => normalizeDocumento(d, index)),
      );
      void queryClient.invalidateQueries({ queryKey: queryKey(scope, entityId) });
    },
  });

  const persist = async (next: DocumentoRequerido[]) => {
    setDocumentos(next);
    await saveMutation.mutateAsync(next);
  };

  const openCreate = () => {
    setModalMode('create');
    setEditingId(null);
    setModalOpen(true);
  };

  const openEdit = (doc: DocumentoRequerido) => {
    setModalMode('edit');
    setEditingId(doc.id);
    setModalOpen(true);
  };

  const handleModalSubmit = async (values: DocumentoRequeridoFormValues) => {
    if (modalMode === 'create') {
      const next: DocumentoRequerido[] = [
        ...documentos,
        {
          id: crypto.randomUUID(),
          fase: FASE_DOCUMENTOS_CLIENTE,
          nombre: values.nombre,
          descripcion: values.descripcion,
          obligatorio: values.obligatorio,
          tipo: values.tipo,
          maxImagenes: values.maxImagenes,
          orden: documentos.length,
        },
      ];
      await persist(next);
    } else if (editingId) {
      const next = documentos.map((d) =>
        d.id === editingId
          ? {
              ...d,
              nombre: values.nombre,
              descripcion: values.descripcion,
              obligatorio: values.obligatorio,
              tipo: values.tipo,
              maxImagenes: values.maxImagenes,
            }
          : d,
      );
      await persist(next);
    }
    setModalOpen(false);
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    const next = documentos.filter((d) => d.id !== deleteTarget.id);
    await persist(next);
    setDeleteTarget(null);
  };

  const saveError = saveMutation.error instanceof Error ? saveMutation.error.message : null;
  const isPending = isLoading || saveMutation.isPending;
  const editingDoc = editingId ? documentos.find((d) => d.id === editingId) : null;

  if (isLoading && !initialized) {
    return (
      <div className="flex min-h-[200px] items-center justify-center text-sm text-muted-foreground">
        Cargando documentación requerida…
      </div>
    );
  }

  return (
    <>
      <div className="panel overflow-hidden">
        <div className="panel-header">
          <div className="panel-header-icon">
            <FileText className="h-5 w-5" />
          </div>
          <div className="flex-1">
            <h2 className="panel-title text-base">{title}</h2>
            {subtitle && <p className="text-sm text-muted-foreground">{subtitle}</p>}
          </div>
          {!readOnly && (
            <Button type="button" size="sm" onClick={openCreate} disabled={isPending}>
              <Plus className="h-4 w-4" />
              Añadir documento
            </Button>
          )}
        </div>

        <div className="p-6 pt-0">
          {saveError && (
            <p
              className="mb-4 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
              role="alert"
            >
              {saveError}
            </p>
          )}

          {documentos.length === 0 ? (
            <div className="rounded-lg border border-dashed border-border bg-muted/20 px-6 py-10 text-center">
              <FileText className="mx-auto mb-3 h-8 w-8 text-muted-foreground/60" />
              <p className="text-sm font-medium text-foreground">Sin documentos definidos</p>
              <p className="mt-1 text-sm text-muted-foreground">
                {readOnly
                  ? 'Este servicio no tiene documentación común configurada.'
                  : 'Añada los documentos que el cliente debe aportar en la etapa 2.'}
              </p>
              {!readOnly && (
                <Button type="button" variant="outline" className="mt-4" onClick={openCreate}>
                  <Plus className="h-4 w-4" />
                  Añadir primer documento
                </Button>
              )}
            </div>
          ) : (
            <ul className="divide-y divide-border rounded-lg border border-border bg-card">
              {documentos.map((item, index) => {
                const imagenesLabel = capturaLabel(item);
                return (
                  <li
                    key={item.id}
                    className="flex items-start gap-4 px-4 py-4 transition-colors hover:bg-muted/20"
                  >
                    <span className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                      {index + 1}
                    </span>
                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <p className="font-medium text-foreground">{item.nombre}</p>
                        <Badge variant={item.obligatorio ? 'default' : 'secondary'}>
                          {item.obligatorio ? 'Obligatorio' : 'Opcional'}
                        </Badge>
                        {imagenesLabel && <Badge variant="outline">{imagenesLabel}</Badge>}
                        {readOnly && <Badge variant="outline">Del servicio</Badge>}
                      </div>
                      {item.descripcion ? (
                        <p className="mt-1 text-sm text-muted-foreground leading-relaxed">
                          {item.descripcion}
                        </p>
                      ) : (
                        <p className="mt-1 text-sm italic text-muted-foreground/70">
                          Sin descripción adicional
                        </p>
                      )}
                    </div>
                    {!readOnly && (
                      <div className="flex shrink-0 items-center gap-1">
                        <button
                          type="button"
                          onClick={() => openEdit(item)}
                          disabled={isPending}
                          className="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-primary disabled:opacity-40"
                          aria-label="Editar documento"
                        >
                          <Pencil className="h-4 w-4" />
                        </button>
                        <button
                          type="button"
                          onClick={() => setDeleteTarget(item)}
                          disabled={isPending}
                          className={cn(
                            'rounded-lg p-2 text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive disabled:opacity-40',
                          )}
                          aria-label="Eliminar documento"
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    )}
                  </li>
                );
              })}
            </ul>
          )}
        </div>
      </div>

      {!readOnly && (
        <>
          <DocumentoRequeridoFormModal
            open={modalOpen}
            mode={modalMode}
            initial={editingDoc}
            isPending={isPending}
            onOpenChange={setModalOpen}
            onSubmit={(values) => void handleModalSubmit(values)}
          />

          <Dialog open={deleteTarget !== null} onOpenChange={(open) => !open && setDeleteTarget(null)}>
            <DialogContent className="sm:max-w-md">
              <DialogHeader>
                <DialogTitle>Eliminar documento</DialogTitle>
                <DialogDescription>
                  ¿Eliminar «{deleteTarget?.nombre}» de la lista de documentación requerida?
                </DialogDescription>
              </DialogHeader>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setDeleteTarget(null)}>
                  Cancelar
                </Button>
                <Button
                  type="button"
                  variant="destructive"
                  onClick={() => void handleDelete()}
                  disabled={isPending}
                >
                  Eliminar
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </>
      )}
    </>
  );
}
