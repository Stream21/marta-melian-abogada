import { X } from 'lucide-react';
import { BloqueRenderer } from '@/components/hoja-encargo/BloqueRenderer';
import { ColumnBlockPalette } from '@/components/hoja-encargo/ColumnBlockPalette';
import { Button } from '@/components/ui/button';
import type { DespachoConfigResponse } from '@/api/client';
import type { BloqueColumns, VariablePreviewContext } from '@/lib/hoja-encargo-variables';
import { normalizeColumnsBlock } from '@/lib/hoja-encargo-variables';
import {
  handleColumnBlockDragOver,
  parseColumnBlockDragData,
} from '@/lib/block-drag';
import { setColumnSlot } from '@/lib/escrito-block-tree';
import { cn } from '@/lib/utils';

interface ColumnsBlockEditorProps {
  bloque: BloqueColumns;
  selected: boolean;
  selectedBlockId: string | null;
  preview: boolean;
  previewContext: VariablePreviewContext;
  despacho?: DespachoConfigResponse;
  logoUrl?: string | null;
  selloUrl?: string | null;
  allBloques: import('@/lib/hoja-encargo-variables').BloqueEscrito[];
  onSelect: () => void;
  onSelectChild: (id: string) => void;
  onChange: (bloque: BloqueColumns) => void;
  onChangeTree: (bloques: import('@/lib/hoja-encargo-variables').BloqueEscrito[]) => void;
}

export function ColumnsBlockEditor({
  bloque,
  selected,
  selectedBlockId,
  preview,
  previewContext,
  despacho,
  logoUrl,
  selloUrl,
  allBloques,
  onSelect,
  onSelectChild,
  onChange,
  onChangeTree,
}: ColumnsBlockEditorProps) {
  const normalized = normalizeColumnsBlock(bloque);
  const gridClass = normalized.columnCount === 1 ? 'grid-cols-1' : 'grid-cols-2';
  const showPalette = !preview && selected;

  const handleDrop = (slotIndex: number, event: React.DragEvent) => {
    event.preventDefault();
    event.stopPropagation();
    const childType = parseColumnBlockDragData(event.dataTransfer);
    if (!childType) return;
    onChangeTree(setColumnSlot(allBloques, normalized.id, slotIndex, childType));
  };

  const handleClearSlot = (slotIndex: number, event: React.MouseEvent) => {
    event.stopPropagation();
    onChangeTree(setColumnSlot(allBloques, normalized.id, slotIndex, null));
  };

  return (
    <div
      className={cn(
        'min-w-0 overflow-hidden rounded-lg border p-3 transition-colors',
        selected && !preview ? 'border-primary bg-primary/5 ring-1 ring-primary/20' : 'border-transparent',
        !preview && 'cursor-pointer hover:border-border hover:bg-muted/20',
      )}
      onClick={onSelect}
    >
      {!preview && (
        <div className="mb-3 space-y-2">
          <p className="section-label">
            Fila de {normalized.columnCount} {normalized.columnCount === 1 ? 'columna' : 'columnas'}
          </p>
          {showPalette && (
            <div className="rounded-md border border-dashed border-border bg-muted/20 p-2">
              <p className="mb-2 text-xs text-muted-foreground">
                Arrastre un componente a una columna vacía
              </p>
              <ColumnBlockPalette />
            </div>
          )}
        </div>
      )}

      <div className={cn('grid gap-4', gridClass)}>
        {normalized.children.map((child, slotIndex) => {
          if (!child) {
            return (
              <div
                key={`slot-${slotIndex}`}
                className={cn(
                  'flex min-h-[140px] flex-col items-center justify-center rounded-lg border border-dashed p-3 transition-colors',
                  showPalette
                    ? 'border-primary/30 bg-primary/5'
                    : 'border-border bg-muted/20',
                )}
                onClick={(event) => event.stopPropagation()}
                onDragOver={showPalette ? handleColumnBlockDragOver : undefined}
                onDrop={showPalette ? (event) => handleDrop(slotIndex, event) : undefined}
              >
                <p className="text-center text-xs text-muted-foreground">
                  {showPalette ? 'Suelte aquí un componente' : 'Columna vacía'}
                </p>
              </div>
            );
          }

          return (
            <div
              key={child.id}
              className="relative min-w-0"
              onClick={(event) => {
                event.stopPropagation();
                onSelectChild(child.id);
              }}
            >
              {!preview && selected && (
                <Button
                  type="button"
                  size="icon"
                  variant="ghost"
                  className="absolute right-1 top-1 z-10 h-6 w-6 text-muted-foreground hover:text-destructive"
                  onClick={(event) => handleClearSlot(slotIndex, event)}
                  aria-label="Quitar componente de la columna"
                >
                  <X className="h-3.5 w-3.5" />
                </Button>
              )}
              <BloqueRenderer
                bloque={child}
                selected={selectedBlockId === child.id}
                preview={preview}
                nested
                despacho={despacho}
                previewContext={previewContext}
                logoUrl={logoUrl}
                selloUrl={selloUrl}
                onSelect={() => onSelectChild(child.id)}
                onChange={(updated) =>
                  onChange(
                    normalizeColumnsBlock({
                      ...normalized,
                      children: normalized.children.map((item, index) =>
                        index === slotIndex ? (updated as typeof child) : item,
                      ),
                    }),
                  )
                }
              />
            </div>
          );
        })}
      </div>
    </div>
  );
}
