import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface SignatureAreaSurfaceProps {
  logoUrl?: string | null;
  children: ReactNode;
  className?: string;
  minHeightClass?: string;
}

export function SignatureAreaSurface({
  logoUrl,
  children,
  className,
  minHeightClass = 'min-h-[100px]',
}: SignatureAreaSurfaceProps) {
  return (
    <div
      className={cn(
        'signature-box-surface relative flex flex-col items-center justify-center overflow-hidden rounded-md border border-border bg-muted/20 px-3 py-4 text-center',
        minHeightClass,
        className,
      )}
    >
      {logoUrl && (
        <img
          src={logoUrl}
          alt=""
          aria-hidden
          className="pointer-events-none absolute left-1/2 top-1/2 z-0 max-h-14 max-w-[72px] -translate-x-1/2 -translate-y-1/2 opacity-[0.12] select-none"
        />
      )}
      <div className="relative z-[1] flex w-full flex-col items-center justify-center">{children}</div>
    </div>
  );
}
