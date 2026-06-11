import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface DespachoConfigSectionProps {
  title: string;
  description?: string;
  children: ReactNode;
  className?: string;
}

export function DespachoConfigSection({ title, description, children, className }: DespachoConfigSectionProps) {
  return (
    <section className={cn('space-y-4 rounded-lg border border-border bg-muted/20 p-5', className)}>
      <div>
        <h3 className="font-medium text-foreground">{title}</h3>
        {description && <p className="mt-1 text-sm text-muted-foreground">{description}</p>}
      </div>
      {children}
    </section>
  );
}
