export const VARIABLE_DRAG_MIME = 'application/x-bufete-variable';

export function setVariableDragData(dataTransfer: DataTransfer, variableKey: string): void {
  dataTransfer.setData(VARIABLE_DRAG_MIME, variableKey);
  dataTransfer.setData('text/plain', `[[${variableKey}]]`);
  dataTransfer.effectAllowed = 'copy';
}

export function parseVariableDragData(dataTransfer: DataTransfer): string | null {
  const key = dataTransfer.getData(VARIABLE_DRAG_MIME);
  if (key) {
    return key;
  }

  const plain = dataTransfer.getData('text/plain').trim();
  const match = plain.match(/^\[\[([A-Z_]+)\]\]$/);
  return match ? match[1] : null;
}

export function handleVariableDragOver(event: DragEvent): void {
  event.preventDefault();
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'copy';
  }
}
