import type { BloqueColumnChildType } from '@/lib/hoja-encargo-variables';
import { setColumnBlockDragData } from '@/lib/block-drag';
import { cn } from '@/lib/utils';

const PALETTE_ITEMS: Array<{ type: BloqueColumnChildType; label: string }> = [
  { type: 'title', label: 'Título' },
  { type: 'text', label: 'Párrafo' },
  { type: 'section', label: 'Sección' },
  { type: 'signature_lawyer', label: 'Firma abogada' },
  { type: 'signature_client', label: 'Firma cliente' },
];

interface ColumnBlockPaletteProps {
  className?: string;
}

export function ColumnBlockPalette({ className }: ColumnBlockPaletteProps) {
  return (
    <div className={cn('flex flex-wrap gap-2', className)}>
      {PALETTE_ITEMS.map((item) => (
        <button
          key={item.type}
          type="button"
          draggable
          onDragStart={(event) => setColumnBlockDragData(event.dataTransfer, item.type)}
          className="cursor-grab rounded-md border border-border bg-card px-2.5 py-1 text-xs font-medium text-foreground shadow-sm transition-colors hover:bg-muted/60 active:cursor-grabbing"
        >
          {item.label}
        </button>
      ))}
    </div>
  );
}
