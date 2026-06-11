import type { BloqueColumnChildType } from '@/lib/hoja-encargo-variables';

export const BLOCK_DRAG_MIME = 'application/x-bufete-column-block';

export function setColumnBlockDragData(dataTransfer: DataTransfer, type: BloqueColumnChildType): void {
  dataTransfer.setData(BLOCK_DRAG_MIME, type);
  dataTransfer.effectAllowed = 'copy';
}

export function parseColumnBlockDragData(dataTransfer: DataTransfer): BloqueColumnChildType | null {
  const type = dataTransfer.getData(BLOCK_DRAG_MIME);
  if (
    type === 'title' ||
    type === 'text' ||
    type === 'section' ||
    type === 'signature_lawyer' ||
    type === 'signature_client'
  ) {
    return type;
  }
  return null;
}

export function handleColumnBlockDragOver(event: React.DragEvent): void {
  event.preventDefault();
  event.dataTransfer.dropEffect = 'copy';
}
