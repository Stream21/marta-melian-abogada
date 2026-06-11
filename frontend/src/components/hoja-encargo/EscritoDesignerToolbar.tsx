import { Eye, EyeOff, Save } from 'lucide-react';
import { BloqueToolbar } from '@/components/hoja-encargo/BloqueToolbar';
import { Button } from '@/components/ui/button';
import type { RootBlockAddType } from '@/lib/hoja-encargo-variables';

interface EscritoDesignerToolbarProps {
  preview: boolean;
  onTogglePreview: () => void;
  onSave: () => void;
  savePending: boolean;
  showBlockToolbar: boolean;
  onAddBlock: (type: RootBlockAddType) => void;
  onRemove: () => void;
  canRemove: boolean;
}

export function EscritoDesignerToolbar({
  preview,
  onTogglePreview,
  onSave,
  savePending,
  showBlockToolbar,
  onAddBlock,
  onRemove,
  canRemove,
}: EscritoDesignerToolbarProps) {
  return (
    <div className="flex shrink-0 items-center justify-between gap-4 rounded-lg border bg-card px-3 py-2 shadow-sm">
      <div className="flex min-w-0 items-center">
        {showBlockToolbar ? (
          <BloqueToolbar
            onAddBlock={onAddBlock}
            onRemove={onRemove}
            canRemove={canRemove}
            disabled={savePending}
          />
        ) : (
          <span className="text-sm text-muted-foreground">Vista previa del PDF final</span>
        )}
      </div>

      <div className="flex shrink-0 items-center gap-2">
        <Button type="button" variant="outline" size="sm" onClick={onTogglePreview}>
          {preview ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
          {preview ? 'Editar' : 'Vista previa'}
        </Button>

        <Button type="button" size="sm" onClick={onSave} disabled={savePending || preview}>
          <Save className="h-4 w-4" />
          Guardar
        </Button>
      </div>
    </div>
  );
}
