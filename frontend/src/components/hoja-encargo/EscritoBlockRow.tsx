import { GripVertical } from 'lucide-react';
import {
  getRowInsertPosition,
  handleRowDragOver,
  type RowInsertPosition,
  setRowDragData,
} from '@/lib/escrito-row-drag';
import { cn } from '@/lib/utils';

interface EscritoBlockRowProps {
  blockId: string;
  selected: boolean;
  dropPosition: RowInsertPosition | null;
  dragging: boolean;
  onDragStart: (blockId: string) => void;
  onDragEnd: () => void;
  onDragOverRow: (blockId: string, position: RowInsertPosition) => void;
  onDrop: (blockId: string, event: React.DragEvent) => void;
  children: React.ReactNode;
}

function DropGuideLine({ position }: { position: RowInsertPosition }) {
  return (
    <div
      className={cn(
        'pointer-events-none absolute inset-x-0 z-20 flex items-center gap-0',
        position === 'before' ? '-top-px' : '-bottom-px',
      )}
      aria-hidden
    >
      <div className="h-2.5 w-2.5 shrink-0 rounded-full bg-primary ring-2 ring-primary/25" />
      <div className="h-0.5 flex-1 rounded-full bg-primary shadow-[0_0_6px_1px] shadow-primary/40" />
    </div>
  );
}

export function EscritoBlockRow({
  blockId,
  selected,
  dropPosition,
  dragging,
  onDragStart,
  onDragEnd,
  onDragOverRow,
  onDrop,
  children,
}: EscritoBlockRowProps) {
  return (
    <div
      className={cn(
        'group relative flex gap-1 rounded-lg transition-opacity',
        dragging && 'opacity-40',
      )}
      onDragOver={(event) => {
        handleRowDragOver(event);
        onDragOverRow(blockId, getRowInsertPosition(event, event.currentTarget));
      }}
      onDrop={(event) => {
        event.preventDefault();
        event.stopPropagation();
        onDrop(blockId, event);
      }}
    >
      {dropPosition && <DropGuideLine position={dropPosition} />}

      <button
        type="button"
        draggable
        aria-label="Arrastrar para reordenar"
        onDragStart={(event) => {
          setRowDragData(event.dataTransfer, blockId);
          onDragStart(blockId);
        }}
        onDragEnd={onDragEnd}
        className={cn(
          'mt-2 flex h-8 w-7 shrink-0 cursor-grab items-center justify-center rounded-md text-muted-foreground',
          'opacity-0 transition-opacity hover:bg-muted/60 group-hover:opacity-100',
          selected && 'opacity-100',
        )}
      >
        <GripVertical className="h-4 w-4" />
      </button>
      <div className="min-w-0 flex-1">{children}</div>
    </div>
  );
}
