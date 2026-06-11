import { cn } from '@/lib/utils';

interface DespachoAssetPreviewProps {
  label: string;
  imageUrl: string | null;
  emptyLabel: string;
  className?: string;
}

export function DespachoAssetPreview({
  label,
  imageUrl,
  emptyLabel,
  className,
}: DespachoAssetPreviewProps) {
  return (
    <div className={cn('space-y-2', className)}>
      <p className="section-label">{label}</p>
      <div className="flex min-h-[120px] items-center justify-center rounded-lg border border-dashed border-border bg-muted/30 p-4">
        {imageUrl ? (
          <img src={imageUrl} alt={label} className="max-h-24 max-w-full object-contain" />
        ) : (
          <p className="text-center text-xs text-muted-foreground">{emptyLabel}</p>
        )}
      </div>
    </div>
  );
}
