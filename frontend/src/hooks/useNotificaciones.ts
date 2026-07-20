import { useCallback, useMemo, useRef } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { api, type NotificacionResponse, type NotificacionesRecientesResponse } from '@/api/client';

const QUERY_KEY = ['notificaciones'] as const;
const DISMISSED_KEY = 'bufete_notificaciones_dismissed';

function loadDismissedIds(): Set<string> {
  try {
    const raw = sessionStorage.getItem(DISMISSED_KEY);
    if (!raw) return new Set();
    const parsed = JSON.parse(raw) as unknown;
    if (!Array.isArray(parsed)) return new Set();
    return new Set(parsed.filter((id): id is string => typeof id === 'string'));
  } catch {
    return new Set();
  }
}

function saveDismissedIds(ids: Set<string>): void {
  if (ids.size === 0) {
    sessionStorage.removeItem(DISMISSED_KEY);
    return;
  }
  sessionStorage.setItem(DISMISSED_KEY, JSON.stringify([...ids]));
}

function applyDismissed(
  data: NotificacionesRecientesResponse,
  dismissed: Set<string>,
): NotificacionesRecientesResponse {
  if (dismissed.size === 0) {
    return data;
  }

  const hiddenInItems = data.items.filter((item) => dismissed.has(item.id)).length;

  return {
    items: data.items.filter((item) => !dismissed.has(item.id)),
    total: Math.max(0, data.total - hiddenInItems),
  };
}

export function useNotificaciones() {
  const queryClient = useQueryClient();
  const dismissedRef = useRef(loadDismissedIds());

  const query = useQuery({
    queryKey: QUERY_KEY,
    queryFn: () => api.getNotificacionesRecientes(),
    refetchInterval: 60_000,
    select: (data) => applyDismissed(data, dismissedRef.current),
  });

  const dismiss = useCallback(
    (id: string) => {
      dismissedRef.current.add(id);
      saveDismissedIds(dismissedRef.current);

      queryClient.setQueryData<NotificacionesRecientesResponse>(QUERY_KEY, (current) => {
        if (!current) {
          return current;
        }

        return applyDismissed(current, dismissedRef.current);
      });
    },
    [queryClient],
  );

  const marcarLeida = useCallback(
    async (id: string) => {
      dismiss(id);
      try {
        await api.marcarNotificacionLeida(id);
      } catch {
        dismissedRef.current.delete(id);
        saveDismissedIds(dismissedRef.current);
        void queryClient.invalidateQueries({ queryKey: QUERY_KEY });
      }
    },
    [dismiss, queryClient],
  );

  const marcarTodasLeidas = useCallback(async () => {
    queryClient.setQueryData<NotificacionesRecientesResponse>(QUERY_KEY, {
      items: [],
      total: 0,
    });
    dismissedRef.current.clear();
    saveDismissedIds(dismissedRef.current);

    try {
      await api.marcarTodasNotificacionesLeidas();
    } catch {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY });
    }
  }, [queryClient]);

  const notificaciones = query.data?.items ?? [];
  const totalPendientes = query.data?.total ?? 0;

  const badgeLabel = useMemo(() => {
    if (totalPendientes <= 0) return null;
    if (totalPendientes > 99) return '99+';
    return String(totalPendientes);
  }, [totalPendientes]);

  return {
    notificaciones,
    totalPendientes,
    badgeLabel,
    isLoading: query.isLoading,
    marcarLeida,
    marcarTodasLeidas,
  };
}

export type { NotificacionResponse };
