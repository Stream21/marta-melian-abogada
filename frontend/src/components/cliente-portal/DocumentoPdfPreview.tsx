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
}

export function DocumentoPdfPreview({
  label,
  previewUrl,
  onFullyRead,
  fullyRead = false,
  requireFullRead = false,
  ctaPrincipal = false,
  ctaLabel,
}: DocumentoPdfPreviewProps) {
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [rendering, setRendering] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [scrollComplete, setScrollComplete] = useState(false);
  const [pageCount, setPageCount] = useState(0);

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
    onFullyReadRef.current?.();
  }, []);

  const tryDetectScrollEnd = useCallback(() => {
    const scrollEl = scrollRef.current;
    if (!scrollEl || scrollCompleteRef.current) return;
    if (isScrollAtEnd(scrollEl)) {
      markFullyRead();
    }
  }, [markFullyRead]);

  const scrollToEnd = useCallback(() => {
    const scrollEl = scrollRef.current;
    if (!scrollEl || scrollCompleteRef.current) return;
    scrollEl.scrollTo({ top: scrollEl.scrollHeight, behavior: 'smooth' });
    window.setTimeout(() => tryDetectScrollEnd(), 450);
  }, [tryDetectScrollEnd]);

  useEffect(() => {
    if (fullyRead) {
      setScrollComplete(true);
      scrollCompleteRef.current = true;
    }
  }, [fullyRead]);

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
      setPageCount(0);
      pagesRef.current?.replaceChildren();
      return;
    }

    if (!fullyRead) {
      setScrollComplete(false);
      scrollCompleteRef.current = false;
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
          tryDetectScrollEnd();
        });
      })
      .catch((err: Error) => {
        if (generation !== fetchGenerationRef.current) return;
        setError(err.message);
        setLoading(false);
        setRendering(false);
      });
    // eslint-disable-next-line react-hooks/exhaustive-deps -- tryDetectScrollEnd es estable
  }, [open, previewUrl]);

  const canClose = !requireFullRead || scrollComplete || fullyRead;
  const needsScrollCue =
    requireFullRead && !scrollComplete && !loading && !rendering && !error && pageCount > 0;

  const handleOpenChange = (next: boolean) => {
    if (!next && open && !canClose) return;
    setOpen(next);
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        <Button
          type="button"
          variant={ctaPrincipal ? 'default' : 'outline'}
          size={ctaPrincipal ? 'lg' : 'sm'}
          className={cn(
            'w-full',
            ctaPrincipal ? 'justify-center gap-2' : 'justify-start gap-2',
          )}
        >
          <Eye className="h-4 w-4 shrink-0" />
          <span className="min-w-0 truncate">
            {ctaLabel ?? (ctaPrincipal ? `Abrir ${label}` : `Ver ${label}`)}
          </span>
        </Button>
      </DialogTrigger>
      {fullyRead && requireFullRead && (
        <p className="mt-2 flex items-center gap-1.5 text-sm font-medium text-emerald-700">
          <CheckCircle2 className="h-4 w-4 shrink-0" />
          Documento revisado hasta el final
        </p>
      )}
      <DialogContent
        className={cn(
          'flex h-[90vh] max-w-4xl flex-col gap-3',
          !canClose && '[&>button.absolute]:hidden',
        )}
        onPointerDownOutside={(e) => {
          if (!canClose) e.preventDefault();
        }}
        onEscapeKeyDown={(e) => {
          if (!canClose) e.preventDefault();
        }}
      >
        <DialogHeader>
          <DialogTitle>{label}</DialogTitle>
          <DialogDescription className="sr-only">
            {requireFullRead && !scrollComplete
              ? 'Desplácese hasta el final del documento para continuar.'
              : 'Vista previa del documento'}
          </DialogDescription>
        </DialogHeader>

        <div
          ref={scrollRef}
          className="min-h-0 flex-1 overflow-y-auto rounded-lg border border-border bg-muted/30 p-3"
          onScroll={tryDetectScrollEnd}
        >
          {(loading || rendering) && (
            <div className="flex h-full min-h-[200px] items-center justify-center text-muted-foreground">
              <Loader2 className="mr-2 h-5 w-5 animate-spin" />
              {loading ? 'Cargando documento…' : 'Preparando vista previa…'}
            </div>
          )}
          {error && (
            <div className="flex h-full min-h-[200px] items-center justify-center p-6 text-center text-sm text-destructive">
              {error}
            </div>
          )}
          <div ref={pagesRef} className={cn((loading || rendering || error) && 'hidden')} />
        </div>

        {needsScrollCue && (
          <Button
            type="button"
            size="lg"
            className="w-full gap-2 bg-amber-500 text-amber-950 hover:bg-amber-400 focus-visible:ring-amber-500"
            onClick={scrollToEnd}
          >
            <ChevronsDown className="h-5 w-5" />
            Ir al final del documento
          </Button>
        )}

        {scrollComplete && requireFullRead && (
          <div
            className="flex items-center gap-3 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-900"
            role="status"
          >
            <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600" />
            <p className="text-sm font-medium">Documento revisado. Ya puede continuar.</p>
          </div>
        )}

        <DialogFooter>
          <Button
            type="button"
            variant={canClose ? 'default' : 'secondary'}
            disabled={!canClose}
            className="w-full sm:w-auto"
            onClick={() => setOpen(false)}
          >
            Listo
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
