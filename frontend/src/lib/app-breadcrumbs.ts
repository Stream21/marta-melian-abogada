export type AppBreadcrumbCrumb = {
  /** Clave estable para deduplicar/truncar el trail (normalmente el pathname). */
  key: string;
  label: string;
  /** Si falta, es el segmento actual (sin enlace) o un ancestro aún no navegable. */
  to?: string;
  params?: Record<string, string>;
};

export type BreadcrumbNavState = {
  breadcrumb?: {
    /** Sustituye el trail (p. ej. al pulsar una miga). */
    set?: AppBreadcrumbCrumb[];
    /** Conserva el trail previo y añade la página actual (saltos laterales con contexto). */
    keepTrail?: boolean;
    /** Ignora el trail y usa solo la jerarquía de la URL. */
    reset?: boolean;
  };
};

/** Inicio = dashboard de KPIs. */
const ROOT: AppBreadcrumbCrumb = {
  key: '/dashboard',
  label: 'Inicio',
  to: '/dashboard',
};

function normalizePath(pathname: string): string {
  return pathname.replace(/\/+$/, '') || '/';
}

/** Ids de ruta desde el pathname (useParams en layouts a veces no trae params hijos). */
function idFromPath(path: string, pattern: RegExp): string | undefined {
  const match = path.match(pattern);
  const id = match?.[1];
  return id && id !== 'nuevo' ? id : undefined;
}

/**
 * Jerarquía estructural a partir de la URL (fallback cuando no hay trail de navegación).
 */
export function buildAppBreadcrumbs(
  pathname: string,
  params: Record<string, string>,
  labels?: {
    expedienteLabel?: string;
    clienteLabel?: string;
    servicioLabel?: string;
    tramiteLabel?: string;
  },
): AppBreadcrumbCrumb[] {
  const path = normalizePath(pathname);
  const crumbs: AppBreadcrumbCrumb[] = [{ ...ROOT }];

  if (path === '/' || path === '/dashboard') {
    return [{ key: '/dashboard', label: 'Inicio' }];
  }

  if (path.startsWith('/expedientes')) {
    crumbs.push({ key: '/expedientes', label: 'Expedientes', to: '/expedientes' });
    if (path === '/expedientes/nuevo') {
      crumbs.push({ key: path, label: 'Nuevo' });
    } else {
      const expedienteId =
        params.expedienteId ?? idFromPath(path, /^\/expedientes\/([^/]+)$/);
      if (expedienteId) {
        crumbs.push({
          key: `/expedientes/${expedienteId}`,
          label: labels?.expedienteLabel ?? expedienteId.slice(0, 8),
          to: '/expedientes/$expedienteId',
          params: { expedienteId },
        });
      }
    }
    return finalizeCurrent(crumbs);
  }

  if (path.startsWith('/clientes')) {
    crumbs.push({ key: '/clientes', label: 'Clientes', to: '/clientes' });
    if (path === '/clientes/nuevo') {
      crumbs.push({ key: path, label: 'Nuevo' });
    } else {
      const clienteId = params.clienteId ?? idFromPath(path, /^\/clientes\/([^/]+)$/);
      if (clienteId) {
        crumbs.push({
          key: `/clientes/${clienteId}`,
          label: labels?.clienteLabel ?? 'Ficha',
          to: '/clientes/$clienteId',
          params: { clienteId },
        });
      }
    }
    return finalizeCurrent(crumbs);
  }

  if (path.startsWith('/agenda')) {
    crumbs.push({ key: '/agenda', label: 'Agenda' });
    return crumbs;
  }

  if (path.startsWith('/facturacion')) {
    crumbs.push({ key: '/facturacion', label: 'Facturación' });
    return crumbs;
  }

  if (path.startsWith('/gastos')) {
    crumbs.push({ key: '/gastos', label: 'Gastos', to: '/gastos' });
    if (path === '/gastos/nuevo') {
      crumbs.push({ key: path, label: 'Nuevo' });
    } else {
      const gastoId = params.gastoId ?? idFromPath(path, /^\/gastos\/([^/]+)$/);
      if (gastoId) {
        crumbs.push({
          key: `/gastos/${gastoId}`,
          label: 'Editar',
          to: '/gastos/$gastoId',
          params: { gastoId },
        });
      }
    }
    return finalizeCurrent(crumbs);
  }

  if (path.startsWith('/config')) {
    // Sin enlace: /config solo redirige a Servicios y confunde en despacho/trámites.
    crumbs.push({ key: '/config', label: 'Configuración' });

    if (path === '/config') {
      return finalizeCurrent(crumbs);
    }

    if (path.startsWith('/config/despacho')) {
      crumbs.push({ key: '/config/despacho', label: 'Datos del despacho' });
      return crumbs;
    }

    if (path.startsWith('/config/servicios')) {
      crumbs.push({ key: '/config/servicios', label: 'Servicios', to: '/config/servicios' });
      if (path === '/config/servicios/nuevo') {
        crumbs.push({ key: path, label: 'Nuevo' });
      } else {
        const servicioId =
          params.servicioId ?? idFromPath(path, /^\/config\/servicios\/([^/]+)/);
        if (servicioId) {
          crumbs.push({
            key: `/config/servicios/${servicioId}`,
            label: labels?.servicioLabel ?? 'Editar',
            to: '/config/servicios/$servicioId',
            params: { servicioId },
          });
        }
      }
      return finalizeCurrent(crumbs);
    }

    if (path.startsWith('/config/tramites')) {
      crumbs.push({ key: '/config/tramites', label: 'Trámites', to: '/config/tramites' });
      if (path === '/config/tramites/nuevo') {
        crumbs.push({ key: path, label: 'Nuevo' });
        return crumbs;
      }
      const tramiteId =
        params.tramiteId ?? idFromPath(path, /^\/config\/tramites\/([^/]+)/);
      if (tramiteId) {
        const tramiteKey = `/config/tramites/${tramiteId}`;
        crumbs.push({
          key: tramiteKey,
          label: labels?.tramiteLabel ?? 'Trámite',
          to: '/config/tramites/$tramiteId',
          params: { tramiteId },
        });
        if (path.includes('/configuracion')) {
          crumbs.push({ key: `${tramiteKey}/configuracion`, label: 'Configuración' });
        } else if (path.includes('/fases')) {
          crumbs.push({ key: `${tramiteKey}/fases`, label: 'Fases' });
        } else if (path.includes('/hoja-encargo')) {
          crumbs.push({ key: `${tramiteKey}/hoja-encargo`, label: 'Hoja de encargo' });
        }
      }
      return finalizeCurrent(crumbs);
    }

    return finalizeCurrent(crumbs);
  }

  return crumbs;
}

/** El último crumb es la página actual: sin enlace. */
function finalizeCurrent(crumbs: AppBreadcrumbCrumb[]): AppBreadcrumbCrumb[] {
  if (crumbs.length === 0) return crumbs;
  const last = crumbs[crumbs.length - 1]!;
  const { to: _to, params: _params, ...rest } = last;
  crumbs[crumbs.length - 1] = rest;
  return crumbs;
}

/**
 * Combina el trail de navegación con la página actual (URL + labels).
 * Si la página ya estaba en el trail, trunca; si no, la añade.
 */
export function mergeNavigationTrail(
  previousTrail: AppBreadcrumbCrumb[],
  currentStructural: AppBreadcrumbCrumb[],
): AppBreadcrumbCrumb[] {
  const currentLeaf = currentStructural[currentStructural.length - 1];
  if (!currentLeaf) {
    return previousTrail.length > 0 ? previousTrail : currentStructural;
  }

  const existingIndex = previousTrail.findIndex((c) => c.key === currentLeaf.key);
  if (existingIndex >= 0) {
    return finalizeCurrent(
      previousTrail.slice(0, existingIndex + 1).map((c, i, arr) => ({
        key: c.key,
        label: i === arr.length - 1 ? currentLeaf.label || c.label : c.label,
        ...(i === arr.length - 1 ? {} : navTargetFromKey(c.key, c)),
      })),
    );
  }

  if (previousTrail.length === 0) {
    return currentStructural;
  }

  const base = previousTrail.map((c) => ({
    key: c.key,
    label: c.label,
    ...navTargetFromKey(c.key, c),
  }));

  return finalizeCurrent([...base, { key: currentLeaf.key, label: currentLeaf.label }]);
}

/** Recupera to/params a partir del key del crumb (paths de detalle). */
export function navTargetFromKey(
  key: string,
  existing?: Pick<AppBreadcrumbCrumb, 'to' | 'params'>,
): Pick<AppBreadcrumbCrumb, 'to' | 'params'> {
  if (existing?.to) {
    return { to: existing.to, params: existing.params };
  }

  const expediente = key.match(/^\/expedientes\/([^/]+)$/);
  if (expediente && expediente[1] !== 'nuevo') {
    return { to: '/expedientes/$expedienteId', params: { expedienteId: expediente[1]! } };
  }

  const cliente = key.match(/^\/clientes\/([^/]+)$/);
  if (cliente && cliente[1] !== 'nuevo') {
    return { to: '/clientes/$clienteId', params: { clienteId: cliente[1]! } };
  }

  const servicio = key.match(/^\/config\/servicios\/([^/]+)$/);
  if (servicio && servicio[1] !== 'nuevo') {
    return { to: '/config/servicios/$servicioId', params: { servicioId: servicio[1]! } };
  }

  const tramite = key.match(/^\/config\/tramites\/([^/]+)$/);
  if (tramite && tramite[1] !== 'nuevo') {
    return { to: '/config/tramites/$tramiteId', params: { tramiteId: tramite[1]! } };
  }

  if (
    key === '/dashboard' ||
    key === '/expedientes' ||
    key === '/clientes' ||
    key === '/facturacion' ||
    key === '/gastos' ||
    key === '/agenda' ||
    key === '/config/servicios' ||
    key === '/config/tramites' ||
    key === '/config/despacho'
  ) {
    return { to: key };
  }

  // /config no tiene pantalla propia (redirige a Servicios).
  if (key === '/config') {
    return {};
  }

  return {};
}

/** Actualiza etiquetas del trail cuando llegan datos (nº expediente, nombre…). */
export function applyTrailLabels(
  trail: AppBreadcrumbCrumb[],
  labels: {
    expedienteId?: string;
    expedienteLabel?: string;
    clienteId?: string;
    clienteLabel?: string;
    servicioId?: string;
    servicioLabel?: string;
    tramiteId?: string;
    tramiteLabel?: string;
    /** Etiquetas por id para crumbs del rastro (p. ej. expediente previo al cliente). */
    expedienteLabels?: Record<string, string>;
    clienteLabels?: Record<string, string>;
  },
): AppBreadcrumbCrumb[] {
  return trail.map((c) => {
    const expedienteMatch = c.key.match(/^\/expedientes\/([^/]+)$/);
    if (expedienteMatch && expedienteMatch[1] !== 'nuevo') {
      const id = expedienteMatch[1]!;
      const label =
        labels.expedienteLabels?.[id] ??
        (labels.expedienteId === id ? labels.expedienteLabel : undefined);
      if (label) return { ...c, label };
    }

    const clienteMatch = c.key.match(/^\/clientes\/([^/]+)$/);
    if (clienteMatch && clienteMatch[1] !== 'nuevo') {
      const id = clienteMatch[1]!;
      const label =
        labels.clienteLabels?.[id] ??
        (labels.clienteId === id ? labels.clienteLabel : undefined);
      if (label) return { ...c, label };
    }

    if (labels.servicioId && c.key === `/config/servicios/${labels.servicioId}` && labels.servicioLabel) {
      return { ...c, label: labels.servicioLabel };
    }
    if (labels.tramiteId && c.key === `/config/tramites/${labels.tramiteId}` && labels.tramiteLabel) {
      return { ...c, label: labels.tramiteLabel };
    }
    return c;
  });
}

export function isSectionEntryPath(pathname: string): boolean {
  const path = normalizePath(pathname);
  return (
    path === '/dashboard' ||
    path === '/expedientes' ||
    path === '/clientes' ||
    path === '/agenda' ||
    path === '/facturacion' ||
    path === '/config' ||
    path === '/config/servicios' ||
    path === '/config/tramites' ||
    path === '/config/despacho'
  );
}
