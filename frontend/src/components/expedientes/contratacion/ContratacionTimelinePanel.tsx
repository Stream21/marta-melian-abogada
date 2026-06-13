import { useEffect, useRef, useCallback } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { CheckCircle2, Circle, Clock, Loader2, PenLine, User } from 'lucide-react';
import { api, type ContratacionHitoResponse } from '@/api/client';

interface ContratacionTimelinePanelProps {
  expedienteId: string;
  initialHitos?: ContratacionHitoResponse[];
  total?: number;
}

function HitoItem({ hito }: { hito: ContratacionHitoResponse }) {
  return (
    <li className="flex gap-3 text-sm">
      <div className="mt-0.5 text-muted-foreground">
        {hito.tipo === 'documento_firmado' || hito.tipo === 'otp_firma_verificado' ? (
          <PenLine className="h-4 w-4 text-emerald-600" />
        ) : hito.actor === 'cliente' ? (
          <User className="h-4 w-4" />
        ) : hito.actor === 'abogado' ? (
          <CheckCircle2 className="h-4 w-4 text-primary" />
        ) : (
          <Circle className="h-4 w-4" />
        )}
      </div>
      <div>
        <p>{hito.descripcion}</p>
        <p className="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
          <Clock className="h-3 w-3" />
          {new Date(hito.createdAt).toLocaleString('es-ES')}
          <span className="capitalize">· {hito.actor}</span>
        </p>
      </div>
    </li>
  );
}

export function ContratacionTimelinePanel({
  expedienteId,
  initialHitos = [],
  total = 0,
}: ContratacionTimelinePanelProps) {
  const scrollRef = useRef<HTMLDivElement>(null);
  const sentinelRef = useRef<HTMLDivElement>(null);

  const {
    data,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    isLoading,
  } = useInfiniteQuery({
    queryKey: ['contratacion-hitos', expedienteId],
    queryFn: ({ pageParam }) => api.getContratacionHitos(expedienteId, pageParam as number, 20),
    initialPageParam: 0,
    getNextPageParam: (lastPage) => (lastPage.hasMore ? lastPage.offset + lastPage.limit : undefined),
    initialData: initialHitos.length > 0
      ? {
          pages: [{
            items: initialHitos,
            total: total || initialHitos.length,
            offset: 0,
            limit: 20,
            hasMore: total > initialHitos.length,
          }],
          pageParams: [0],
        }
      : undefined,
    staleTime: 5000,
  });

  const hitos = data?.pages.flatMap((p) => p.items) ?? [];
  const totalHitos = data?.pages[0]?.total ?? total;

  const loadMore = useCallback(() => {
    if (hasNextPage && !isFetchingNextPage) {
      void fetchNextPage();
    }
  }, [fetchNextPage, hasNextPage, isFetchingNextPage]);

  useEffect(() => {
    const sentinel = sentinelRef.current;
    const root = scrollRef.current;
    if (!sentinel || !root) return;

    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0]?.isIntersecting) loadMore();
      },
      { root, rootMargin: '80px', threshold: 0 },
    );

    observer.observe(sentinel);
    return () => observer.disconnect();
  }, [loadMore]);

  return (
    <div
      ref={scrollRef}
      className="max-h-72 overflow-y-auto pr-1 -mr-1"
      aria-label="Línea de tiempo del expediente"
    >
      {isLoading && hitos.length === 0 ? (
        <p className="text-sm text-muted-foreground py-4 text-center">Cargando actividad…</p>
      ) : hitos.length === 0 ? (
        <p className="text-sm text-muted-foreground">Sin actividad registrada aún.</p>
      ) : (
        <>
          <p className="text-xs text-muted-foreground mb-3">
            {totalHitos} registro{totalHitos !== 1 ? 's' : ''} — desplácese para cargar más
          </p>
          <ul className="space-y-3">
            {hitos.map((hito) => (
              <HitoItem key={hito.id} hito={hito} />
            ))}
          </ul>
          <div ref={sentinelRef} className="h-4 shrink-0" aria-hidden />
          {isFetchingNextPage && (
            <div className="flex justify-center py-3 text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
            </div>
          )}
        </>
      )}
    </div>
  );
}
