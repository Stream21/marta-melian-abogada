import {
  type ReactNode,
  type RefObject,
  useCallback,
  useEffect,
  useRef,
  useState,
} from 'react';
import { ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Detecta si un contenedor tiene contenido desbordado y si el usuario ya ha
 * empezado a desplazarse (para ocultar la pista de scroll).
 */
export function useScrollHint(scrollRef: RefObject<HTMLElement | null>) {
  const [puedeScroll, setPuedeScroll] = useState(false);
  const [haScrollado, setHaScrollado] = useState(false);

  const medir = useCallback(() => {
    const el = scrollRef.current;
    if (!el) return;
    setPuedeScroll(el.scrollHeight > el.clientHeight + 12);
  }, [scrollRef]);

  useEffect(() => {
    const el = scrollRef.current;
    if (!el) return;

    medir();
    setHaScrollado(false);

    const onScroll = () => {
      if (el.scrollTop > 8) setHaScrollado(true);
    };

    el.addEventListener('scroll', onScroll, { passive: true });

    const ro = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(medir) : null;
    ro?.observe(el);
    if (el.firstElementChild) ro?.observe(el.firstElementChild);

    window.addEventListener('resize', medir);
    window.visualViewport?.addEventListener('resize', medir);

    return () => {
      el.removeEventListener('scroll', onScroll);
      ro?.disconnect();
      window.removeEventListener('resize', medir);
      window.visualViewport?.removeEventListener('resize', medir);
    };
  }, [scrollRef, medir]);

  return {
    mostrarPista: puedeScroll && !haScrollado,
    medir,
  };
}

type PortalScrollAreaProps = {
  children: ReactNode;
  className?: string;
  contentClassName?: string;
  /** Texto breve opcional; por defecto solo icono + “Más abajo”. */
  etiqueta?: string;
  showHint?: boolean;
};

/**
 * Scroll del portal con pista superior moderna (chip corto). Se oculta al mover.
 */
export function PortalScrollArea({
  children,
  className,
  contentClassName,
  etiqueta = 'Más abajo',
  showHint = true,
}: PortalScrollAreaProps) {
  const scrollRef = useRef<HTMLDivElement>(null);
  const { mostrarPista } = useScrollHint(scrollRef);

  return (
    <div className={cn('relative flex min-h-0 min-w-0 flex-1 flex-col', className)}>
      {showHint && mostrarPista && (
        <div
          className="pointer-events-none absolute inset-x-0 top-0 z-20 flex justify-center px-3 pt-2"
          aria-hidden
        >
          <div
            className={cn(
              'inline-flex items-center gap-1.5 rounded-full border border-border/80 bg-card/95',
              'px-3 py-1.5 text-sm font-medium text-foreground shadow-sm backdrop-blur-sm',
              'motion-safe:animate-scroll-hint-pulse',
            )}
          >
            <ChevronDown className="h-4 w-4 text-primary motion-safe:animate-bounce" />
            {etiqueta}
          </div>
        </div>
      )}

      <div
        ref={scrollRef}
        className={cn(
          'min-h-0 min-w-0 flex-1 overflow-y-auto overscroll-contain [-webkit-overflow-scrolling:touch]',
          contentClassName,
        )}
      >
        {children}
      </div>
    </div>
  );
}
