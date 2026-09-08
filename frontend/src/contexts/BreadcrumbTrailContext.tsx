import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';
import { useParams, useRouterState } from '@tanstack/react-router';
import {
  applyTrailLabels,
  buildAppBreadcrumbs,
  isSectionEntryPath,
  mergeNavigationTrail,
  type AppBreadcrumbCrumb,
  type BreadcrumbNavState,
} from '@/lib/app-breadcrumbs';

const STORAGE_KEY = 'bufete.app-breadcrumb-trail';

type BreadcrumbTrailContextValue = {
  trail: AppBreadcrumbCrumb[];
  /** Al pinchar una miga: trail según URL destino (sin reinyectar un set viejo). */
  stateForCrumbIndex: (index: number) => BreadcrumbNavState;
  syncLabels: (labels: BreadcrumbLabels) => void;
};

const BreadcrumbTrailContext = createContext<BreadcrumbTrailContextValue | null>(null);

function readStoredTrail(): AppBreadcrumbCrumb[] {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw) as unknown;
    return Array.isArray(parsed) ? (parsed as AppBreadcrumbCrumb[]) : [];
  } catch {
    return [];
  }
}

function writeStoredTrail(trail: AppBreadcrumbCrumb[]): void {
  try {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(trail));
  } catch {
    // ignore
  }
}

function trailSignature(trail: AppBreadcrumbCrumb[]): string {
  return trail.map((c) => `${c.key}|${c.label}|${c.to ?? ''}`).join('>');
}

export type BreadcrumbLabels = {
  expedienteId?: string;
  expedienteLabel?: string;
  clienteId?: string;
  clienteLabel?: string;
  servicioId?: string;
  servicioLabel?: string;
  tramiteId?: string;
  tramiteLabel?: string;
  expedienteLabels?: Record<string, string>;
  clienteLabels?: Record<string, string>;
};

export function BreadcrumbTrailProvider({ children }: { children: ReactNode }) {
  const pathname = useRouterState({ select: (s) => s.location.pathname });
  const locationHref = useRouterState({ select: (s) => s.location.href });
  const locationState = useRouterState({
    select: (s) => (s.location.state ?? {}) as BreadcrumbNavState,
  });
  const params = useParams({ strict: false }) as Record<string, string>;
  const [trail, setTrail] = useState<AppBreadcrumbCrumb[]>(() => readStoredTrail());
  const [labels, setLabels] = useState<BreadcrumbLabels>({});
  const lastProcessed = useRef<{ href: string; structuralSig: string; navKey: string } | null>(
    null,
  );
  const labelsRef = useRef(labels);
  labelsRef.current = labels;

  const structural = useMemo(
    () =>
      buildAppBreadcrumbs(pathname, params, {
        expedienteLabel: labels.expedienteLabel,
        clienteLabel: labels.clienteLabel,
        servicioLabel: labels.servicioLabel,
        tramiteLabel: labels.tramiteLabel,
      }),
    [
      pathname,
      params,
      labels.expedienteLabel,
      labels.clienteLabel,
      labels.servicioLabel,
      labels.tramiteLabel,
    ],
  );

  useEffect(() => {
    const nav = locationState.breadcrumb;
    const navKey = nav?.set
      ? `set:${trailSignature(nav.set)}`
      : nav?.keepTrail
        ? 'keep'
        : nav?.reset
          ? 'reset'
          : '';
    const structuralSig = trailSignature(structural);
    const prev = lastProcessed.current;
    if (
      prev &&
      prev.href === locationHref &&
      prev.structuralSig === structuralSig &&
      prev.navKey === navKey
    ) {
      return;
    }
    lastProcessed.current = { href: locationHref, structuralSig, navKey };

    setTrail((prevTrail) => {
      const stored = prevTrail.length > 0 ? prevTrail : readStoredTrail();
      let next: AppBreadcrumbCrumb[];

      if (nav?.set) {
        // Solo honrar set si la hoja coincide con la URL actual (evita estado tras redirects).
        const setLeaf = nav.set[nav.set.length - 1]?.key;
        const structuralLeaf = structural[structural.length - 1]?.key;
        next =
          setLeaf && structuralLeaf && setLeaf === structuralLeaf
            ? nav.set
            : structural;
      } else if (nav?.keepTrail) {
        next = mergeNavigationTrail(stored, structural);
      } else if (nav?.reset || isSectionEntryPath(pathname)) {
        next = structural;
      } else {
        const leafKey = structural[structural.length - 1]?.key;
        const inTrail = leafKey ? stored.some((c) => c.key === leafKey) : false;
        next = inTrail ? mergeNavigationTrail(stored, structural) : structural;
      }

      next = applyTrailLabels(next, labelsRef.current);
      if (trailSignature(next) === trailSignature(prevTrail)) {
        return prevTrail;
      }
      writeStoredTrail(next);
      return next;
    });
  }, [locationHref, locationState, pathname, structural]);

  const syncLabels = useCallback((nextLabels: BreadcrumbLabels) => {
    setLabels(nextLabels);
    setTrail((prev) => {
      if (prev.length === 0) return prev;
      const next = applyTrailLabels(prev, nextLabels);
      const changed = next.some((c, i) => c.label !== prev[i]?.label);
      if (!changed) return prev;
      writeStoredTrail(next);
      return next;
    });
  }, []);

  const stateForCrumbIndex = useCallback(
    (_index: number): BreadcrumbNavState => ({
      breadcrumb: { reset: true },
    }),
    [],
  );

  const value = useMemo(
    () => ({ trail, stateForCrumbIndex, syncLabels }),
    [trail, stateForCrumbIndex, syncLabels],
  );

  return (
    <BreadcrumbTrailContext.Provider value={value}>{children}</BreadcrumbTrailContext.Provider>
  );
}

export function useBreadcrumbTrail(): BreadcrumbTrailContextValue {
  const ctx = useContext(BreadcrumbTrailContext);
  if (!ctx) {
    throw new Error('useBreadcrumbTrail must be used within BreadcrumbTrailProvider');
  }
  return ctx;
}

export function breadcrumbKeepTrailState(): BreadcrumbNavState {
  return { breadcrumb: { keepTrail: true } };
}

export function breadcrumbResetState(): BreadcrumbNavState {
  return { breadcrumb: { reset: true } };
}
