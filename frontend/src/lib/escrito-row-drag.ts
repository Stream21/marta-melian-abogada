import type { BloqueEscrito } from '@/lib/hoja-encargo-variables';

export const ROW_DRAG_MIME = 'application/x-bufete-escrito-row';

export function setRowDragData(dataTransfer: DataTransfer, blockId: string): void {
  dataTransfer.setData(ROW_DRAG_MIME, blockId);
  dataTransfer.effectAllowed = 'move';
}

export function parseRowDragData(dataTransfer: DataTransfer): string | null {
  const id = dataTransfer.getData(ROW_DRAG_MIME);
  return id || null;
}

export type RowInsertPosition = 'before' | 'after';

export function handleRowDragOver(event: React.DragEvent): void {
  event.preventDefault();
  event.dataTransfer.dropEffect = 'move';
}

export function getRowInsertPosition(
  event: React.DragEvent,
  element: HTMLElement,
): RowInsertPosition {
  const rect = element.getBoundingClientRect();
  const midpoint = rect.top + rect.height / 2;
  return event.clientY < midpoint ? 'before' : 'after';
}

export function reorderRootBlocks(
  bloques: BloqueEscrito[],
  draggedId: string,
  targetId: string,
  position: RowInsertPosition,
): BloqueEscrito[] {
  if (draggedId === targetId) {
    return bloques;
  }

  const fromIndex = bloques.findIndex((bloque) => bloque.id === draggedId);
  const targetIndex = bloques.findIndex((bloque) => bloque.id === targetId);

  if (fromIndex < 0 || targetIndex < 0) {
    return bloques;
  }

  let insertIndex = position === 'before' ? targetIndex : targetIndex + 1;

  const next = [...bloques];
  const [item] = next.splice(fromIndex, 1);

  if (fromIndex < insertIndex) {
    insertIndex -= 1;
  }

  next.splice(insertIndex, 0, item);

  return next;
}
