import { useState } from 'react';
import { Columns2, Plus, Square, Trash2 } from 'lucide-react';
import type { RootBlockAddType } from '@/lib/hoja-encargo-variables';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';

const ROW_OPTIONS: Array<{
  type: Extract<RootBlockAddType, 'columns_1' | 'columns_2'>;
  label: string;
  hint: string;
  icon: typeof Square;
}> = [
  {
    type: 'columns_1',
    label: 'Fila de 1 columna',
    hint: 'Un espacio para título, texto, sección o firma',
    icon: Square,
  },
  {
    type: 'columns_2',
    label: 'Fila de 2 columnas',
    hint: 'Dos espacios en paralelo (p. ej. firmas)',
    icon: Columns2,
  },
];

interface BloqueToolbarProps {
  onAddBlock: (type: RootBlockAddType) => void;
  onRemove: () => void;
  canRemove: boolean;
  disabled?: boolean;
}

export function BloqueToolbar({ onAddBlock, onRemove, canRemove, disabled }: BloqueToolbarProps) {
  const [dialogOpen, setDialogOpen] = useState(false);

  const handleAddRow = (type: Extract<RootBlockAddType, 'columns_1' | 'columns_2'>) => {
    onAddBlock(type);
    setDialogOpen(false);
  };

  return (
    <div className="flex items-center gap-2">
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogTrigger asChild>
          <Button type="button" variant="outline" size="sm" disabled={disabled}>
            <Plus className="h-4 w-4" />
            Añadir fila
          </Button>
        </DialogTrigger>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Añadir fila al documento</DialogTitle>
            <DialogDescription>
              Elija el tipo de división. Dentro de cada columna podrá arrastrar componentes.
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-2">
            {ROW_OPTIONS.map((option) => {
              const Icon = option.icon;
              return (
                <button
                  key={option.type}
                  type="button"
                  onClick={() => handleAddRow(option.type)}
                  className="flex items-start gap-3 rounded-lg border border-border bg-card p-3 text-left transition-colors hover:border-primary/40 hover:bg-primary/5"
                >
                  <span className="mt-0.5 rounded-md bg-primary/10 p-2 text-primary">
                    <Icon className="h-4 w-4" />
                  </span>
                  <span>
                    <span className="block text-sm font-medium text-foreground">{option.label}</span>
                    <span className="mt-0.5 block text-xs text-muted-foreground">{option.hint}</span>
                  </span>
                </button>
              );
            })}
          </div>
        </DialogContent>
      </Dialog>

      <Button
        type="button"
        variant="outline"
        size="sm"
        onClick={onRemove}
        disabled={disabled || !canRemove}
        className="text-destructive hover:text-destructive"
      >
        <Trash2 className="h-4 w-4" />
        Eliminar
      </Button>
    </div>
  );
}
