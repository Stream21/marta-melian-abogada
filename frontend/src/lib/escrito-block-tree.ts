import {
  createColumnChild,
  normalizeColumnsBlock,
  type BloqueColumnChildType,
  type BloqueColumns,
  type BloqueEscrito,
} from '@/lib/hoja-encargo-variables';

export type BlockLocation = {
  block: BloqueEscrito;
  parent: BloqueColumns | null;
  index: number;
  rootIndex: number;
};

export function locateBlock(bloques: BloqueEscrito[], id: string): BlockLocation | null {
  for (let rootIndex = 0; rootIndex < bloques.length; rootIndex += 1) {
    const bloque = bloques[rootIndex];
    if (bloque.id === id) {
      return { block: bloque, parent: null, index: rootIndex, rootIndex };
    }

    if (bloque.type === 'columns') {
      for (let index = 0; index < bloque.children.length; index += 1) {
        const child = bloque.children[index];
        if (child?.id === id) {
          return { block: child, parent: bloque, index, rootIndex };
        }
      }
    }
  }

  return null;
}

export function updateBlockInTree(bloques: BloqueEscrito[], updated: BloqueEscrito): BloqueEscrito[] {
  return bloques.map((bloque) => {
    if (bloque.id === updated.id) {
      return updated;
    }

    if (bloque.type === 'columns') {
      return normalizeColumnsBlock({
        ...bloque,
        children: bloque.children.map((child) =>
          child && child.id === updated.id ? (updated as typeof child) : child,
        ),
      });
    }

    return bloque;
  });
}

export function removeBlockFromTree(bloques: BloqueEscrito[], id: string): BloqueEscrito[] {
  const location = locateBlock(bloques, id);
  if (!location) return bloques;

  if (!location.parent) {
    return bloques.filter((bloque) => bloque.id !== id);
  }

  return updateBlockInTree(bloques, normalizeColumnsBlock({
    ...location.parent,
    children: location.parent.children.map((child, index) =>
      index === location.index ? null : child,
    ),
  }));
}

export function setColumnSlot(
  bloques: BloqueEscrito[],
  columnsId: string,
  slotIndex: number,
  childType: BloqueColumnChildType | null,
): BloqueEscrito[] {
  const location = locateBlock(bloques, columnsId);
  if (!location || location.block.type !== 'columns') {
    return bloques;
  }

  const columns = normalizeColumnsBlock(location.block);
  if (slotIndex < 0 || slotIndex >= columns.columnCount) {
    return bloques;
  }

  const children = [...columns.children];
  children[slotIndex] = childType ? createColumnChild(childType) : null;

  return updateBlockInTree(bloques, normalizeColumnsBlock({ ...columns, children }));
}
