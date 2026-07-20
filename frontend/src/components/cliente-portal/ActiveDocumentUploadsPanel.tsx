import { Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface ActiveDocumentUpload {
  docId: string;
  docLabel: string;
  fileNames: string[];
}

interface ActiveDocumentUploadsPanelProps {
  uploads: ActiveDocumentUpload[];
  className?: string;
}

export function ActiveDocumentUploadsPanel({ uploads, className }: ActiveDocumentUploadsPanelProps) {
  if (uploads.length === 0) {
    return null;
  }

  return (
    <div
      className={cn('rounded-lg border border-primary/25 bg-primary/5 p-4', className)}
      role="status"
      aria-live="polite"
    >
      <p className="text-sm font-semibold text-foreground">
        Subidas en curso ({uploads.length})
      </p>
      <p className="mt-1 text-xs text-muted-foreground">
        Cada archivo se convierte a PDF por separado. No cierre la página mientras dure la subida.
      </p>
      <ul className="mt-3 space-y-3">
        {uploads.map((upload) => (
          <li
            key={upload.docId}
            className="flex items-start gap-3 rounded-md border border-border/60 bg-card/80 px-3 py-2.5 text-sm"
          >
            <Loader2 className="mt-0.5 h-4 w-4 shrink-0 animate-spin text-primary" aria-hidden />
            <div className="min-w-0 flex-1">
              <p className="font-medium leading-tight">{upload.docLabel}</p>
              <ul className="mt-1.5 space-y-0.5">
                {upload.fileNames.map((fileName, index) => (
                  <li
                    key={`${upload.docId}-${fileName}-${index}`}
                    className="truncate text-xs text-muted-foreground"
                  >
                    {fileName}
                  </li>
                ))}
              </ul>
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
}
