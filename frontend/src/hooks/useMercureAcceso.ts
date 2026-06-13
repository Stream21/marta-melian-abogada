import { useEffect, useRef } from 'react';
import { useQueryClient, type QueryKey } from '@tanstack/react-query';
import { api } from '@/api/client';
import { buildMercureEventSourceUrl } from '@/lib/mercureHubUrl';

const POLL_MS = 8000;

export function useMercureAcceso(token: string, enabled = true) {
  const queryClient = useQueryClient();
  const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    if (!enabled || !token) return;

    let eventSource: EventSource | null = null;
    let cancelled = false;

    const keys: QueryKey[] = [['acceso', token]];

    const refresh = () => {
      void queryClient.refetchQueries({ queryKey: keys[0], type: 'active' });
    };

    pollRef.current = setInterval(refresh, POLL_MS);

    const connect = async () => {
      try {
        const { hubUrl, topic, token: mercureToken } = await api.getMercureTokenAcceso(token);
        if (cancelled) return;

        eventSource = new EventSource(buildMercureEventSourceUrl(hubUrl, [topic], mercureToken));
        eventSource.onmessage = refresh;
        eventSource.onerror = () => {
          eventSource?.close();
          eventSource = null;
        };
      } catch {
        // Polling como respaldo
      }
    };

    void connect();

    return () => {
      cancelled = true;
      eventSource?.close();
      if (pollRef.current) clearInterval(pollRef.current);
    };
  }, [enabled, token, queryClient]);
}
