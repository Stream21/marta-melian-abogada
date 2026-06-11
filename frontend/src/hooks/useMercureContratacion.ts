import { useEffect } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { api } from '@/api/client';

export function useMercureContratacion(expedienteId: string, enabled = true) {
  const queryClient = useQueryClient();

  useEffect(() => {
    if (!enabled) return;

    let eventSource: EventSource | null = null;
    let cancelled = false;

    const connect = async () => {
      try {
        const { hubUrl, topic, token } = await api.getMercureToken(expedienteId);
        if (cancelled) return;

        const mercurePublicUrl = import.meta.env.VITE_MERCURE_PUBLIC_URL as string | undefined;
        const baseHub = mercurePublicUrl
          ? `${mercurePublicUrl.replace(/\/$/, '')}/.well-known/mercure`
          : hubUrl;

        const url = new URL(baseHub);
        url.searchParams.set('topic', topic);
        url.searchParams.set('authorization', `Bearer ${token}`);

        eventSource = new EventSource(url.toString());
        eventSource.onmessage = () => {
          void queryClient.invalidateQueries({ queryKey: ['contratacion', expedienteId] });
          void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
        };
      } catch {
        // Mercure opcional en desarrollo
      }
    };

    void connect();

    return () => {
      cancelled = true;
      eventSource?.close();
    };
  }, [enabled, expedienteId, queryClient]);
}
