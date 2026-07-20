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
}

export function DocumentoPdfPreview({
  label,
  previewUrl,
  onFullyRead,
  fullyRead = false,
  requireFullRead = false,
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

  const handleOpenChange = (next: boolean) => {
    if (!next && open && !canClose) return;
    setOpen(next);
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm" className="w-full justify-start">
          <Eye className="mr-2 h-4 w-4" />
          Ver {label}
          {fullyRead && (
            <span className="ml-auto flex items-center gap-1 text-xs text-emerald-600">
              <CheckCircle2 className="h-3 w-3" />
              Revisado
            </span>
          )}
        </Button>
      </DialogTrigger>
      <DialogContent
        className={cn(
          'max-w-4xl h-[90vh] flex flex-col gap-3',
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
          <DialogDescription>
            {requireFullRead && !scrollComplete
              ? 'Desplácese hasta el final del documento para poder cerrar esta ventana y continuar. También puede usar el botón «Ir al final».'
              : 'Revise el documento con atención antes de firmar.'}
          </DialogDescription>
        </DialogHeader>

        <div
          ref={scrollRef}
          className="flex-1 min-h-0 overflow-y-auto rounded-lg border bg-muted/30 p-3"
          onScroll={tryDetectScrollEnd}
        >
          {(loading || rendering) && (
            <div className="flex h-full min-h-[200px] items-center justify-center text-muted-foreground">
              <Loader2 className="mr-2 h-5 w-5 animate-spin" />
              {loading ? 'Cargando documento…' : 'Preparando vista previa…'}
            </div>
          )}
          {error && (
            <div className="flex h-full min-h-[200px] items-center justify-center p-6 text-destructive text-sm text-center">
              {error}
            </div>
          )}
          <div ref={pagesRef} className={cn((loading || rendering || error) && 'hidden')} />
        </div>

        {pageCount > 0 && !scrollComplete && requireFullRead && !loading && !rendering && (
          <Button
            type="button"
            variant="outline"
            className="w-full"
            onClick={scrollToEnd}
          >
            <ChevronsDown className="mr-2 h-4 w-4" />
            Ir al final del documento
          </Button>
        )}

        {pageCount > 0 && !scrollComplete && requireFullRead && (
          <p className="text-xs text-amber-800 rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
            {pageCount === 1
              ? 'Desplácese por el documento hasta el final para marcarlo como revisado.'
              : `Documento de ${pageCount} páginas — continúe desplazándose hasta la última página.`}
          </p>
        )}

        {scrollComplete && requireFullRead && (
          <p className="text-xs text-emerald-800 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 flex items-center gap-2">
            <CheckCircle2 className="h-4 w-4 shrink-0" />
            Documento revisado. Puede cerrar esta ventana.
          </p>
        )}

        <DialogFooter>
          <Button
            type="button"
            variant={canClose ? 'default' : 'secondary'}
            disabled={!canClose}
            onClick={() => setOpen(false)}
          >
            {canClose ? 'Cerrar' : 'Lea hasta el final para cerrar'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
