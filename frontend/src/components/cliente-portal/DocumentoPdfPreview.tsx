import { useCallback, useEffect, useRef, useState } from 'react';
import { CheckCircle2, ChevronsDown, Eye, Loader2 } from 'lucide-react';
import { fetchAccesoPdf } from '@/api/client';
import { isScrollAtEnd, renderPdfPages } from '@/lib/pdf-viewer';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

interface DocumentoPdfPreviewProps {
  label: string;
  previewUrl: string;
  /** Se invoca solo cuando el usuario ha llegado al final del documento (o encaja en pantalla). */
  onFullyRead?: () => void;
  fullyRead?: boolean;
  /** Si true, exige lectura completa antes de cerrar el diálogo. */
  requireFullRead?: boolean;
  /** Botón más visible (flujo de firma del portal). */
  ctaPrincipal?: boolean;
  /** Texto del botón; por defecto «Abrir {label}». */
  ctaLabel?: string;
  /** Abre el documento al montar (p. ej. justo después del briefing). */
  autoOpen?: boolean;
}

export function DocumentoPdfPreview({
  label,
  previewUrl,
  onFullyRead,
  fullyRead = false,
  requireFullRead = false,
  ctaPrincipal = false,
  ctaLabel,
  autoOpen = false,
}: DocumentoPdfPreviewProps) {
  const [open, setOpen] = useState(autoOpen && !fullyRead);
  const [loading, setLoading] = useState(false);
  const [rendering, setRendering] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [scrollComplete, setScrollComplete] = useState(fullyRead);
  const [pageCount, setPageCount] = useState(0);
  const [progresoLectura, setProgresoLectura] = useState(0);

  const scrollRef = useRef<HTMLDivElement>(null);
  const pagesRef = useRef<HTMLDivElement>(null);
  const blobUrlRef = useRef<string | null>(null);
  const onFullyReadRef = useRef(onFullyRead);
  const scrollCompleteRef = useRef(scrollComplete);
  const fetchGenerationRef = useRef(0);

  onFullyReadRef.current = onFullyRead;
  scrollCompleteRef.current = scrollComplete;

  const markFullyRead = useCallback(() => {
    if (scrollCompleteRef.current) return;
    setScrollComplete(true);
    scrollCompleteRef.current = true;
    setProgresoLectura(100);
    onFullyReadRef.current?.();
  }, []);

  const actualizarProgreso = useCallback(() => {
    const scrollEl = scrollRef.current;
    if (!scrollEl) return;

    const maxScroll = scrollEl.scrollHeight - scrollEl.clientHeight;
    if (maxScroll <= 8) {
      setProgresoLectura(100);
      markFullyRead();
      return;
    }

    const pct = Math.min(100, Math.round((scrollEl.scrollTop / maxScroll) * 100));
    setProgresoLectura(pct);

    if (isScrollAtEnd(scrollEl)) {
      markFullyRead();
    }
  }, [markFullyRead]);

  const scrollToEnd = useCallback(() => {
    const scrollEl = scrollRef.current;
    if (!scrollEl || scrollCompleteRef.current) return;
    scrollEl.scrollTo({ top: scrollEl.scrollHeight, behavior: 'smooth' });
    window.setTimeout(() => actualizarProgreso(), 450);
  }, [actualizarProgreso]);

  useEffect(() => {
    if (fullyRead) {
      setScrollComplete(true);
      scrollCompleteRef.current = true;
      setProgresoLectura(100);
    }
  }, [fullyRead]);

  useEffect(() => {
    if (autoOpen && !fullyRead) {
      setOpen(true);
    }
  }, [autoOpen, fullyRead, previewUrl]);

  useEffect(() => {
    if (!open) {
      fetchGenerationRef.current += 1;
      if (blobUrlRef.current) {
        URL.revokeObjectURL(blobUrlRef.current);
        blobUrlRef.current = null;
      }
      setError(null);
      setLoading(false);
      setRendering(false);
      setScrollComplete(fullyRead);
      scrollCompleteRef.current = fullyRead;
      setProgresoLectura(fullyRead ? 100 : 0);
      setPageCount(0);
      pagesRef.current?.replaceChildren();
      return;
    }

    if (!fullyRead) {
      setScrollComplete(false);
      scrollCompleteRef.current = false;
      setProgresoLectura(0);
    }

    const generation = ++fetchGenerationRef.current;
    setLoading(true);
    setError(null);
    setPageCount(0);

    void fetchAccesoPdf(previewUrl)
      .then(async (blobUrl) => {
        if (generation !== fetchGenerationRef.current) {
          URL.revokeObjectURL(blobUrl);
          return;
        }

        blobUrlRef.current = blobUrl;
        setLoading(false);
        setRendering(true);

        const container = pagesRef.current;
        if (!container) {
          URL.revokeObjectURL(blobUrl);
          return;
        }

        const numPages = await renderPdfPages(container, blobUrl);
        if (generation !== fetchGenerationRef.current) return;

        setPageCount(numPages);
        setRendering(false);

        requestAnimationFrame(() => {
          actualizarProgreso();
        });
      })
      .catch((err: Error) => {
        if (generation !== fetchGenerationRef.current) return;
        setError(err.message);
        setLoading(false);
        setRendering(false);
      });
    // eslint-disable-next-line react-hooks/exhaustive-deps -- actualizarProgreso es estable
  }, [open, previewUrl]);

  const canClose = !requireFullRead || scrollComplete || fullyRead;
  const needsScrollCue =
    requireFullRead && !scrollComplete && !loading && !rendering && !error && pageCount > 0;

  const handleOpenChange = (next: boolean) => {
    if (!next && open && !canClose) return;
    setOpen(next);
  };

  const cerrarTrasLectura = () => {
    if (!canClose) return;
    setOpen(false);
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      {!autoOpen || fullyRead || scrollComplete ? (
        <DialogTrigger asChild>
          <Button
            type="button"
            variant={ctaPrincipal ? 'default' : 'outline'}
            size={ctaPrincipal ? 'lg' : 'sm'}
            className={cn(
              'w-full min-h-[44px]',
              ctaPrincipal ? 'justify-center gap-2' : 'justify-start gap-2',
            )}
          >
            <Eye className="h-4 w-4 shrink-0" />
            <span className="min-w-0 truncate">
              {ctaLabel ??
                (fullyRead || scrollComplete
                  ? `Volver a ver ${label}`
                  : ctaPrincipal
                    ? `Abrir ${label}`
                    : `Ver ${label}`)}
            </span>
          </Button>
        </DialogTrigger>
      ) : (
        <div className="flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-border bg-muted/40 px-3 py-3 text-sm text-muted-foreground">
          <Loader2 className="h-4 w-4 animate-spin motion-reduce:animate-none" />
          Abriendo el documento…
        </div>
      )}

      {(fullyRead || scrollComplete) && requireFullRead && !open && (
        <p className="mt-2 flex items-center gap-1.5 text-sm font-medium text-emerald-700">
          <CheckCircle2 className="h-4 w-4 shrink-0" />
          Documento revisado hasta el final
        </p>
      )}

      <DialogContent
        className={cn(
          'flex w-full max-w-none flex-col gap-0 overflow-hidden rounded-none border-0 p-0',
          'fixed inset-x-0 bottom-0 top-0 z-50 translate-x-0 translate-y-0',
          'h-[var(--portal-vh,100dvh)] max-h-[var(--portal-vh,100dvh)]',
          'sm:left-[50%] sm:top-[50%] sm:h-[90vh] sm:max-h-[90vh] sm:max-w-4xl sm:translate-x-[-50%] sm:translate-y-[-50%] sm:rounded-xl sm:border sm:p-0',
          !canClose && '[&>button.absolute]:hidden',
        )}
        onPointerDownOutside={(e) => {
          if (!canClose) e.preventDefault();
        }}
        onEscapeKeyDown={(e) => {
          if (!canClose) e.preventDefault();
        }}
      >
        <DialogHeader className="shrink-0 space-y-1 border-b border-border px-4 py-3 pr-12 text-left sm:px-5">
          <DialogTitle className="text-base sm:text-lg">{label}</DialogTitle>
          <DialogDescription className="text-sm leading-snug text-muted-foreground">
            {requireFullRead && !scrollComplete
              ? 'Baja hasta el final para poder seguir. La barra muestra tu avance.'
              : 'Listo. Pulsa continuar para firmar.'}
          </DialogDescription>
          {requireFullRead && pageCount > 0 && (
            <div className="space-y-1.5 pt-2">
              <div className="flex items-center justify-between gap-2 text-xs text-muted-foreground">
                <span>{pageCount === 1 ? '1 página' : `${pageCount} páginas`}</span>
                <span className="font-semibold text-foreground">{progresoLectura}%</span>
              </div>
              <div
                className="h-2.5 overflow-hidden rounded-full bg-muted"
                role="progressbar"
                aria-valuenow={progresoLectura}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-label="Progreso de lectura"
              >
                <div
                  className="h-full rounded-full bg-primary transition-[width] duration-200 ease-out"
                  style={{ width: `${progresoLectura}%` }}
                />
              </div>
            </div>
          )}
        </DialogHeader>

        <div className="relative min-h-0 flex-1">
          <div
            ref={scrollRef}
            className="h-full overflow-y-auto overscroll-contain bg-muted/30 px-3 py-3 sm:px-4"
            onScroll={actualizarProgreso}
          >
            {(loading || rendering) && (
              <div className="flex h-full min-h-[200px] items-center justify-center text-muted-foreground">
                <Loader2 className="mr-2 h-5 w-5 animate-spin motion-reduce:animate-none" />
                {loading ? 'Cargando…' : 'Preparando…'}
              </div>
            )}
            {error && (
              <div className="flex h-full min-h-[200px] items-center justify-center p-6 text-center text-sm text-destructive">
                {error}
              </div>
            )}
            <div ref={pagesRef} className={cn((loading || rendering || error) && 'hidden')} />
          </div>
        </div>

        <DialogFooter className="shrink-0 flex-col gap-2 border-t border-border bg-card px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:flex-col sm:px-5">
          {needsScrollCue && (
            <Button
              type="button"
              variant="default"
              size="lg"
              className="min-h-[52px] w-full gap-2 text-base font-semibold shadow-md"
              onClick={scrollToEnd}
            >
              <ChevronsDown className="h-5 w-5" />
              Ir al final
            </Button>
          )}
          {scrollComplete && requireFullRead ? (
            <div
              className="flex items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-2.5 text-emerald-900"
              role="status"
            >
              <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600" />
              <p className="text-sm font-medium">Has llegado al final. Ya puedes continuar.</p>
            </div>
          ) : null}
          <Button
            type="button"
            variant={canClose ? 'default' : 'secondary'}
            disabled={!canClose}
            className={cn('min-h-[48px] w-full', needsScrollCue && 'opacity-90')}
            size="lg"
            onClick={cerrarTrasLectura}
          >
            {canClose
              ? requireFullRead
                ? 'Continuar'
                : 'Cerrar'
              : 'Primero llega al final'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
