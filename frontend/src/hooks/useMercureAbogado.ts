import { useEffect, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { api } from '@/api/client';
import { buildMercureEventSourceUrl } from '@/lib/mercureHubUrl';

const POLL_MS = 8000;

export function useMercureAbogado(enabled = true) {
  const queryClient = useQueryClient();
  const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    if (!enabled) return;

    let eventSource: EventSource | null = null;
    let cancelled = false;

    const refresh = () => {
      void queryClient.refetchQueries({ queryKey: ['notificaciones'], type: 'active' });
      void queryClient.refetchQueries({ queryKey: ['expedientes'], type: 'active' });
      void queryClient.refetchQueries({ queryKey: ['contratacion'], type: 'active' });
    };

    pollRef.current = setInterval(refresh, POLL_MS);

    const connect = async () => {
      try {
        const { hubUrl, topic, token } = await api.getMercureTokenAbogado();
        if (cancelled) return;

        eventSource = new EventSource(buildMercureEventSourceUrl(hubUrl, [topic], token));
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
  }, [enabled, queryClient]);
}
