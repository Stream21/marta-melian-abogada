import { Upload } from 'lucide-react';
import { cn } from '@/lib/utils';

interface FileUploadProgressPanelProps {
  fileCount?: number;
  fileNames?: string[];
  className?: string;
  title?: string;
  description?: string;
}

export function FileUploadProgressPanel({
  fileCount = 1,
  fileNames,
  className,
  title,
  description,
}: FileUploadProgressPanelProps) {
  const count = fileNames?.length ?? fileCount;
  const heading = title ?? 'Subiendo documentos…';
  const detail =
    description ??
    (count > 1
      ? `Convirtiendo ${count} archivos en un único PDF. Con imágenes grandes puede tardar hasta un minuto.`
      : 'Convirtiendo el archivo a PDF. Con imágenes grandes puede tardar unos segundos.');

  return (
    <div
      className={cn('flex flex-col items-center gap-3 px-4 py-2 text-center', className)}
      role="status"
      aria-live="polite"
      aria-busy="true"
    >
      <div className="relative flex h-14 w-14 items-center justify-center">
        <span
          className="absolute inset-0 animate-spin rounded-full border-2 border-primary/15 border-t-primary"
          aria-hidden
        />
        <span
          className="absolute inset-1.5 animate-spin rounded-full border-2 border-transparent border-t-primary/50 [animation-direction:reverse] [animation-duration:1.6s]"
          aria-hidden
        />
        <span className="relative flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
          <Upload className="h-4 w-4 text-primary motion-safe:animate-bounce" aria-hidden />
        </span>
      </div>

      <div className="space-y-1">
        <p className="text-sm font-semibold text-foreground">{heading}</p>
        <p className="max-w-xs text-xs leading-relaxed text-muted-foreground">{detail}</p>
      </div>

      {fileNames && fileNames.length > 0 && (
        <ul className="w-full max-w-xs space-y-0.5 rounded-md border border-border/60 bg-muted/30 px-3 py-2 text-left">
          {fileNames.map((name, index) => (
            <li key={`${name}-${index}`} className="truncate text-xs text-foreground">
              {name}
            </li>
          ))}
        </ul>
      )}

      <div className="upload-progress-indeterminate w-full max-w-[220px]" aria-hidden />
    </div>
  );
}
