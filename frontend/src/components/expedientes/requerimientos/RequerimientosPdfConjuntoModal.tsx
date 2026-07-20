import { useEffect, useMemo, useState } from 'react';
import { ArrowDown, ArrowUp, FileStack, Loader2 } from 'lucide-react';
import { api, type RequerimientosDocumentoResponse } from '@/api/client';
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

interface RequerimientosPdfConjuntoModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  expedienteId: string;
  documentos: RequerimientosDocumentoResponse[];
}

/** Cada PDF individual generado en un requisito (no el requisito como bloque opaco). */
interface PdfConjuntoArchivoItem {
  /** Id en expediente_documento_archivo; vacío si solo existe archivo legacy en la entrega. */
  archivoId: string;
  /** Solo para entregas legacy sin filas en expediente_documento_archivo. */
  legacyDocumentoId?: string;
  archivoNombre: string;
  requisitoId: string;
  requisitoNombre: string;
  obligatorio: boolean;
  selected: boolean;
}

function downloadBlob(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = filename;
  anchor.click();
  URL.revokeObjectURL(url);
}

function buildArchivoItems(documentos: RequerimientosDocumentoResponse[]): PdfConjuntoArchivoItem[] {
  const items: PdfConjuntoArchivoItem[] = [];

  documentos
    .filter((doc) => doc.estado === 'validado' && doc.tieneArchivo)
    .sort((a, b) => a.orden - b.orden)
    .forEach((doc) => {
      const archivos = [...(doc.archivos ?? [])].sort((a, b) => a.orden - b.orden);
      if (archivos.length === 0) {
        items.push({
          archivoId: '',
          legacyDocumentoId: doc.id,
          archivoNombre: 'documento.pdf',
          requisitoId: doc.id,
          requisitoNombre: doc.nombre,
          obligatorio: doc.obligatorio,
          selected: true,
        });
        return;
      }

      archivos.forEach((archivo) => {
        items.push({
          archivoId: archivo.id,
          archivoNombre: archivo.nombre,
          requisitoId: doc.id,
          requisitoNombre: doc.nombre,
          obligatorio: doc.obligatorio,
          selected: true,
        });
      });
    });

  return items;
}

function itemKeyFor(item: PdfConjuntoArchivoItem): string {
  return item.archivoId !== '' ? item.archivoId : `doc:${item.legacyDocumentoId ?? item.requisitoId}`;
}

function itemIdForApi(item: PdfConjuntoArchivoItem): string {
  return item.archivoId !== '' ? item.archivoId : `doc:${item.legacyDocumentoId ?? item.requisitoId}`;
}

export function RequerimientosPdfConjuntoModal({
  open,
  onOpenChange,
  expedienteId,
  documentos,
}: RequerimientosPdfConjuntoModalProps) {
  const [items, setItems] = useState<PdfConjuntoArchivoItem[]>([]);
  const [generando, setGenerando] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const totalElegibles = useMemo(
    () =>
      documentos
        .filter((doc) => doc.estado === 'validado' && doc.tieneArchivo)
        .reduce((acc, doc) => {
          const n = doc.archivos?.length ?? 0;
          return acc + (n > 0 ? n : 1);
        }, 0),
    [documentos],
  );

  useEffect(() => {
    if (!open) return;
    setItems(buildArchivoItems(documentos));
    setError(null);
    setGenerando(false);
  }, [open, documentos]);

  const seleccionados = items.filter((item) => item.selected);

  const requisitosIncluidos = useMemo(() => {
    const ids = new Set(seleccionados.map((item) => item.requisitoId));
    return ids.size;
  }, [seleccionados]);

  const toggleSelected = (itemKey: string) => {
    setItems((prev) =>
      prev.map((item) =>
        itemKeyFor(item) === itemKey ? { ...item, selected: !item.selected } : item,
      ),
    );
  };

  const seleccionarTodos = (selected: boolean) => {
    setItems((prev) => prev.map((item) => ({ ...item, selected })));
  };

  const mover = (index: number, direction: -1 | 1) => {
    const target = index + direction;
    if (target < 0 || target >= items.length) return;
    setItems((prev) => {
      const next = [...prev];
      [next[index], next[target]] = [next[target], next[index]];
      return next;
    });
  };

  const generarPdf = async () => {
    const archivoIds = seleccionados.map((item) => itemIdForApi(item));
    if (archivoIds.length === 0) return;

    setGenerando(true);
    setError(null);
    try {
      const { blob, filename } = await api.generarPdfConjuntoRequerimientos(expedienteId, archivoIds);
      downloadBlob(blob, filename);
      onOpenChange(false);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'No se pudo generar el PDF conjunto.');
    } finally {
      setGenerando(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="!flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden">
        <DialogHeader className="shrink-0">
          <DialogTitle className="flex items-center gap-2">
            <FileStack className="h-5 w-5 text-primary" />
            PDF conjunto para Mercurio
          </DialogTitle>
          <DialogDescription>
            Elija qué PDFs individuales incluir y en qué orden. Cada archivo subido en un requisito
            aparece por separado; al generar se unirán todos en un único documento para presentar en
            Mercurio.
          </DialogDescription>
        </DialogHeader>

        {totalElegibles === 0 ? (
          <p className="text-sm text-muted-foreground py-4 text-center">
            No hay archivos PDF validados disponibles todavía.
          </p>
        ) : (
          <div className="flex min-h-0 flex-1 flex-col gap-3 overflow-hidden">
            <div className="shrink-0 rounded-lg border border-primary/20 bg-primary/5 px-3 py-2.5 text-sm">
              <p className="font-medium text-foreground">
                Resultado: {seleccionados.length} PDF
                {seleccionados.length !== 1 ? 's' : ''} → 1 documento final
              </p>
              <p className="mt-0.5 text-xs text-muted-foreground">
                {requisitosIncluidos} requisito{requisitosIncluidos !== 1 ? 's' : ''} · orden de
                arriba a abajo = orden en el PDF
              </p>
            </div>

            <div className="flex shrink-0 flex-wrap items-center justify-between gap-2">
              <p className="text-sm text-muted-foreground">
                {seleccionados.length} de {items.length} archivo
                {items.length !== 1 ? 's' : ''} seleccionado
                {seleccionados.length !== 1 ? 's' : ''}
              </p>
              <div className="flex gap-2">
                <Button type="button" variant="ghost" size="sm" onClick={() => seleccionarTodos(true)}>
                  Todos
                </Button>
                <Button type="button" variant="ghost" size="sm" onClick={() => seleccionarTodos(false)}>
                  Ninguno
                </Button>
              </div>
            </div>

            <ul className="min-h-0 max-h-[min(420px,45vh)] space-y-1 overflow-y-auto overscroll-contain rounded-lg border border-border p-2 pr-1">
              {items.map((item, index) => {
                const mostrarCabeceraRequisito =
                  index === 0 || items[index - 1].requisitoId !== item.requisitoId;

                return (
                  <li key={itemKeyFor(item)}>
                    {mostrarCabeceraRequisito && (
                      <p className="mb-1 mt-2 first:mt-0 px-1 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                        {item.requisitoNombre}
                        {item.obligatorio && (
                          <span className="ml-2 normal-case font-normal">· Obligatorio</span>
                        )}
                      </p>
                    )}
                    <div
                      className={cn(
                        'flex items-center gap-2 rounded-md border px-3 py-2 text-sm',
                        item.selected ? 'border-border bg-card' : 'border-transparent bg-muted/40 opacity-70',
                      )}
                    >
                      <input
                        type="checkbox"
                        checked={item.selected}
                        onChange={() => toggleSelected(itemKeyFor(item))}
                        className="h-4 w-4 shrink-0 rounded border-border text-primary focus:ring-ring"
                        aria-label={`Incluir ${item.archivoNombre}`}
                      />
                      <div className="min-w-0 flex-1">
                        <p className="truncate font-medium">{item.archivoNombre}</p>
                        <p className="truncate text-xs text-muted-foreground">
                          {item.selected
                            ? `Posición ${seleccionados.findIndex((s) => itemKeyFor(s) === itemKeyFor(item)) + 1} en el PDF final`
                            : 'Excluido del PDF final'}
                        </p>
                      </div>
                      <div className="flex shrink-0 flex-col gap-0.5">
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          className="h-7 w-7"
                          disabled={index === 0}
                          onClick={() => mover(index, -1)}
                          aria-label="Subir en el orden"
                        >
                          <ArrowUp className="h-3.5 w-3.5" />
                        </Button>
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          className="h-7 w-7"
                          disabled={index === items.length - 1}
                          onClick={() => mover(index, 1)}
                          aria-label="Bajar en el orden"
                        >
                          <ArrowDown className="h-3.5 w-3.5" />
                        </Button>
                      </div>
                    </div>
                  </li>
                );
              })}
            </ul>
          </div>
        )}

        {error && <p className="shrink-0 text-sm text-destructive">{error}</p>}

        <DialogFooter className="shrink-0">
          <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={generando}>
            Cancelar
          </Button>
          <Button
            type="button"
            disabled={generando || seleccionados.length === 0 || totalElegibles === 0}
            onClick={() => void generarPdf()}
          >
            {generando ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Uniendo {seleccionados.length} PDF…
              </>
            ) : (
              `Generar PDF (${seleccionados.length} archivo${seleccionados.length !== 1 ? 's' : ''})`
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
