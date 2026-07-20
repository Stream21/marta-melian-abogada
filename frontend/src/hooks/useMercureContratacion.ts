import { useEffect, useRef } from 'react';
import { useQueryClient, type QueryKey } from '@tanstack/react-query';
import { api } from '@/api/client';
import { buildMercureEventSourceUrl } from '@/lib/mercureHubUrl';

const POLL_MS = 8000;

function refetchLive(queryClient: ReturnType<typeof useQueryClient>, keys: QueryKey[]) {
  keys.forEach((key) => {
    void queryClient.refetchQueries({ queryKey: key, type: 'active' });
  });
}

export function useMercureContratacion(expedienteId: string, enabled = true) {
  const queryClient = useQueryClient();
  const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    if (!enabled) return;

    let eventSource: EventSource | null = null;
    let cancelled = false;

    const keys: QueryKey[] = [
      ['contratacion', expedienteId],
      ['requerimientos', expedienteId],
      ['facturacion', expedienteId],
      ['payments', expedienteId],
      ['escritos', expedienteId],
      ['expediente-auditoria', expedienteId],
      ['expediente', expedienteId],
      ['expedientes'],
      ['notificaciones'],
    ];

    const refresh = () => refetchLive(queryClient, keys);

    pollRef.current = setInterval(refresh, POLL_MS);

    const connect = async () => {
      try {
        const { hubUrl, topic, token } = await api.getMercureToken(expedienteId);
        if (cancelled) return;

        eventSource = new EventSource(
          buildMercureEventSourceUrl(hubUrl, [topic, '/abogado/notificaciones'], token),
        );

        eventSource.onmessage = refresh;
        eventSource.onerror = () => {
          eventSource?.close();
          eventSource = null;
        };
      } catch {
        // Polling mantiene la UI reactiva si Mercure no está disponible
      }
    };

    void connect();

    return () => {
      cancelled = true;
      eventSource?.close();
      if (pollRef.current) clearInterval(pollRef.current);
    };
  }, [enabled, expedienteId, queryClient]);
}
