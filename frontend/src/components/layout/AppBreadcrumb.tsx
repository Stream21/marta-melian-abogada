import { Link, useParams, useRouterState } from '@tanstack/react-router';
import { useQueries, useQuery } from '@tanstack/react-query';
import { useEffect, useMemo } from 'react';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { api } from '@/api/client';
import {
  BreadcrumbTrailProvider,
  useBreadcrumbTrail,
} from '@/contexts/BreadcrumbTrailContext';
import {
  formatClienteBreadcrumbLabel,
  formatExpedienteBreadcrumbLabel,
  formatServicioBreadcrumbLabel,
  formatTramiteBreadcrumbLabel,
} from '@/lib/breadcrumb-labels';
import { cn } from '@/lib/utils';

const linkClass =
  'rounded-md px-0.5 -mx-0.5 text-[12px] text-muted-foreground transition-colors hover:text-foreground';

function extractEntityId(trailKey: string, prefix: string): string | undefined {
  if (!trailKey.startsWith(prefix)) return undefined;
  const id = trailKey.slice(prefix.length);
  return id && !id.includes('/') ? id : undefined;
}

function enrichCrumbLabel(
  key: string,
  fallback: string,
  expedienteLabels: Record<string, string>,
  clienteLabels: Record<string, string>,
): string {
  const expId = extractEntityId(key, '/expedientes/');
  if (expId && expedienteLabels[expId]) return expedienteLabels[expId];
  const cliId = extractEntityId(key, '/clientes/');
  if (cliId && clienteLabels[cliId]) return clienteLabels[cliId];
  return fallback;
}

function idFromPathname(pathname: string, prefix: string): string | undefined {
  const path = pathname.replace(/\/+$/, '') || '/';
  if (!path.startsWith(prefix)) return undefined;
  const id = path.slice(prefix.length).split('/')[0];
  return id && id !== 'nuevo' ? id : undefined;
}

function AppBreadcrumbInner() {
  const pathname = useRouterState({ select: (s) => s.location.pathname });
  const params = useParams({ strict: false }) as Record<string, string>;
  const { trail, stateForCrumbIndex, syncLabels } = useBreadcrumbTrail();

  const routeExpedienteId =
    params.expedienteId ?? idFromPathname(pathname, '/expedientes/');
  const routeClienteId = params.clienteId ?? idFromPathname(pathname, '/clientes/');
  const routeServicioId =
    params.servicioId ?? idFromPathname(pathname, '/config/servicios/');
  const routeTramiteId = params.tramiteId ?? idFromPathname(pathname, '/config/tramites/');

  const expedienteIds = useMemo(() => {
    const ids = new Set<string>();
    if (routeExpedienteId && routeExpedienteId !== 'nuevo') ids.add(routeExpedienteId);
    for (const crumb of trail) {
      const id = extractEntityId(crumb.key, '/expedientes/');
      if (id && id !== 'nuevo') ids.add(id);
    }
    return [...ids];
  }, [trail, routeExpedienteId]);

  const clienteIds = useMemo(() => {
    const ids = new Set<string>();
    if (routeClienteId && routeClienteId !== 'nuevo') ids.add(routeClienteId);
    for (const crumb of trail) {
      const id = extractEntityId(crumb.key, '/clientes/');
      if (id && id !== 'nuevo') ids.add(id);
    }
    return [...ids];
  }, [trail, routeClienteId]);

  const expedienteQueries = useQueries({
    queries: expedienteIds.map((id) => ({
      queryKey: ['expediente', id],
      queryFn: () => api.getExpediente(id),
      staleTime: 60_000,
    })),
  });

  const clienteQueries = useQueries({
    queries: clienteIds.map((id) => ({
      queryKey: ['cliente', id],
      queryFn: () => api.getCliente(id),
      staleTime: 60_000,
    })),
  });

  const { data: servicio } = useQuery({
    queryKey: ['servicio', routeServicioId],
    queryFn: () => api.getServicio(routeServicioId!),
    enabled: Boolean(routeServicioId && routeServicioId !== 'nuevo'),
  });
  const { data: tramite } = useQuery({
    queryKey: ['tramite', routeTramiteId],
    queryFn: () => api.getTramite(routeTramiteId!),
    enabled: Boolean(routeTramiteId && routeTramiteId !== 'nuevo'),
  });

  const expedienteLabels = useMemo(() => {
    const map: Record<string, string> = {};
    expedienteIds.forEach((id, i) => {
      const data = expedienteQueries[i]?.data;
      if (data) map[id] = formatExpedienteBreadcrumbLabel(data);
    });
    return map;
    // dataUpdatedAt estabiliza el memo frente a nuevas referencias de useQueries
    // eslint-disable-next-line react-hooks/exhaustive-deps -- intentional
  }, [expedienteIds.join('|'), expedienteQueries.map((q) => `${q.dataUpdatedAt}:${q.status}`).join('|')]);

  const clienteLabels = useMemo(() => {
    const map: Record<string, string> = {};
    clienteIds.forEach((id, i) => {
      const data = clienteQueries[i]?.data;
      if (data?.cliente) map[id] = formatClienteBreadcrumbLabel(data.cliente);
    });
    return map;
    // eslint-disable-next-line react-hooks/exhaustive-deps -- intentional
  }, [clienteIds.join('|'), clienteQueries.map((q) => `${q.dataUpdatedAt}:${q.status}`).join('|')]);

  const servicioLabel = servicio ? formatServicioBreadcrumbLabel(servicio) : undefined;
  const tramiteLabel = tramite ? formatTramiteBreadcrumbLabel(tramite) : undefined;

  useEffect(() => {
    syncLabels({
      expedienteId: routeExpedienteId,
      expedienteLabel: routeExpedienteId
        ? expedienteLabels[routeExpedienteId]
        : undefined,
      clienteId: routeClienteId,
      clienteLabel: routeClienteId ? clienteLabels[routeClienteId] : undefined,
      servicioId: routeServicioId,
      servicioLabel,
      tramiteId: routeTramiteId,
      tramiteLabel,
      expedienteLabels,
      clienteLabels,
    });
  }, [
    routeExpedienteId,
    routeClienteId,
    routeServicioId,
    routeTramiteId,
    expedienteLabels,
    clienteLabels,
    servicioLabel,
    tramiteLabel,
    syncLabels,
  ]);

  if (
    pathname === '/' ||
    pathname === '/dashboard' ||
    (trail.length <= 1 && !trail[0]?.to)
  ) {
    return null;
  }

  return (
    <div className="shrink-0 border-b border-border bg-card px-4 py-2 lg:px-6">
      <Breadcrumb>
        <BreadcrumbList className="flex-wrap items-center gap-1.5 text-[12px] sm:gap-2">
          {trail.map((crumb, index) => {
            const isLast = index === trail.length - 1;
            const label = enrichCrumbLabel(
              crumb.key,
              crumb.label,
              expedienteLabels,
              clienteLabels,
            );
            // /config no tiene pantalla propia (redirige a Servicios).
            const crumbTo = crumb.key === '/config' ? undefined : crumb.to;
            return (
              <span key={`${crumb.key}-${index}`} className="contents">
                {index > 0 && <BreadcrumbSeparator className="[&>svg]:text-border" />}
                <BreadcrumbItem>
                  {isLast ? (
                    <BreadcrumbPage
                      className="max-w-[min(100%,28rem)] truncate rounded-md bg-primary/10 px-2 py-0.5 text-[12px] font-medium text-primary"
                      title={label}
                    >
                      {label}
                    </BreadcrumbPage>
                  ) : crumbTo ? (
                    <BreadcrumbLink asChild>
                      <Link
                        to={crumbTo as never}
                        params={(crumb.params ?? {}) as never}
                        state={stateForCrumbIndex(index) as never}
                        className={cn(linkClass, 'max-w-[min(100%,22rem)] truncate')}
                        title={label}
                      >
                        {label}
                      </Link>
                    </BreadcrumbLink>
                  ) : (
                    <span
                      className="max-w-[min(100%,22rem)] truncate text-[12px] text-muted-foreground"
                      title={label}
                    >
                      {label}
                    </span>
                  )}
                </BreadcrumbItem>
              </span>
            );
          })}
        </BreadcrumbList>
      </Breadcrumb>
    </div>
  );
}

/**
 * Breadcrumb global: jerarquía de URL + rastro de navegación con etiquetas legibles
 * (nº expediente · cliente, nombre de cliente, etc.).
 */
export function AppBreadcrumb() {
  return (
    <BreadcrumbTrailProvider>
      <AppBreadcrumbInner />
    </BreadcrumbTrailProvider>
  );
}
