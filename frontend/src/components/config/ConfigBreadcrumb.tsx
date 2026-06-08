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

export type ConfigSection = 'servicios' | 'tramites';
export type ConfigBreadcrumbVariant = 'list' | 'nuevo' | 'edit' | 'fases';

interface ConfigBreadcrumbProps {
  section: ConfigSection;
  variant: ConfigBreadcrumbVariant;
}

const sectionLabels: Record<ConfigSection, string> = {
  servicios: 'Servicios',
  tramites: 'Trámites',
};

const sectionPaths: Record<ConfigSection, string> = {
  servicios: '/config/servicios',
  tramites: '/config/tramites',
};

export function ConfigBreadcrumb({ section, variant }: ConfigBreadcrumbProps) {
  const label = sectionLabels[section];
  const path = sectionPaths[section];

  return (
    <div className="shrink-0 border-b bg-card px-6 py-2 md:px-8">
      <Breadcrumb>
        <BreadcrumbList className="flex-wrap items-center gap-2 text-[12px] sm:gap-2">
          <BreadcrumbItem>
            <span className="text-[12px] text-muted-foreground">Configuración</span>
          </BreadcrumbItem>
          <BreadcrumbSeparator className="[&>svg]:text-border" />

          <BreadcrumbItem>
            {variant === 'list' ? (
              <span className={cn(activeClass)} aria-current="page">
                {label}
              </span>
            ) : (
              <BreadcrumbLink asChild>
                <Link to={path as never} className={linkClass}>
                  {label}
                </Link>
              </BreadcrumbLink>
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

          {variant === 'edit' && (
            <>
              <BreadcrumbSeparator className="[&>svg]:text-border" />
              <BreadcrumbItem>
                <span className={activeClass} aria-current="page">
                  Editar
                </span>
              </BreadcrumbItem>
            </>
          )}

          {variant === 'fases' && (
            <>
              <BreadcrumbSeparator className="[&>svg]:text-border" />
              <BreadcrumbItem>
                <span className={activeClass} aria-current="page">
                  Configurar fases
                </span>
              </BreadcrumbItem>
            </>
          )}
        </BreadcrumbList>
      </Breadcrumb>
    </div>
  );
}
