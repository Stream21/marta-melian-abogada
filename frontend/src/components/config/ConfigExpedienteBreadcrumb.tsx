import { Link } from '@tanstack/react-router';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { cn } from '@/lib/utils';

const linkClass =
  'text-[12px] text-muted-foreground transition-colors hover:text-foreground rounded-md px-0.5 -mx-0.5';

const activeClass =
  'text-[12px] font-medium rounded-md bg-primary/10 px-2 py-0.5 text-primary';

export type ConfigExpedienteBreadcrumbVariant = 'list' | 'nuevo';

interface ConfigExpedienteBreadcrumbProps {
  variant: ConfigExpedienteBreadcrumbVariant;
}

export function ConfigExpedienteBreadcrumb({ variant }: ConfigExpedienteBreadcrumbProps) {
  return (
    <div className="shrink-0 border-b bg-card px-6 py-2 md:px-8">
      <Breadcrumb>
        <BreadcrumbList className="flex-wrap items-center gap-2 text-[12px] sm:gap-2">
          <BreadcrumbItem>
            <BreadcrumbLink asChild>
              <Link to="/config" className={linkClass}>
                Configuración
              </Link>
            </BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbSeparator className="[&>svg]:text-border" />

          <BreadcrumbItem>
            <BreadcrumbLink asChild>
              <Link to="/config/tipos-caso" className={linkClass}>
                Expediente
              </Link>
            </BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbSeparator className="[&>svg]:text-border" />

          <BreadcrumbItem>
            {variant === 'nuevo' ? (
              <BreadcrumbLink asChild>
                <Link to="/config/tipos-caso" className={linkClass}>
                  Tipos de caso
                </Link>
              </BreadcrumbLink>
            ) : (
              <span className={cn(activeClass)} aria-current="page">
                Tipos de caso
              </span>
            )}
          </BreadcrumbItem>

          {variant === 'nuevo' && (
            <>
              <BreadcrumbSeparator className="[&>svg]:text-border" />
              <BreadcrumbItem>
                <span className={activeClass} aria-current="page">
                  Nuevo
                </span>
              </BreadcrumbItem>
            </>
          )}
        </BreadcrumbList>
      </Breadcrumb>
    </div>
  );
}
