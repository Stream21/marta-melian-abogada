import { NotebookPen } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { ExpedienteNotasPanel } from '@/components/expedientes/ExpedienteNotasPanel';
import { cn } from '@/lib/utils';

interface ExpedienteNotasSheetProps {
  expedienteId: string;
  expedienteLabel: string;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function ExpedienteNotasSheet({
  expedienteId,
  expedienteLabel,
  open,
  onOpenChange,
}: ExpedienteNotasSheetProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        className={cn(
          'fixed inset-y-0 right-0 left-auto top-0 z-50 flex h-full max-h-full w-full max-w-md translate-x-0 translate-y-0 flex-col gap-0 overflow-hidden rounded-none border-l p-0 shadow-xl',
          'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
          'data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right',
          'sm:rounded-none',
        )}
      >
        <DialogHeader className="shrink-0 space-y-1 border-b px-5 py-4 pr-12 text-left">
          <DialogTitle className="flex items-center gap-2 text-base">
            <span className="panel-header-icon">
              <NotebookPen className="h-4 w-4" />
            </span>
            Notas
          </DialogTitle>
          <DialogDescription className="text-sm">{expedienteLabel}</DialogDescription>
        </DialogHeader>

        <ExpedienteNotasPanel
          expedienteId={expedienteId}
          enabled={open && !!expedienteId}
          compact
        />
      </DialogContent>
    </Dialog>
  );
}
