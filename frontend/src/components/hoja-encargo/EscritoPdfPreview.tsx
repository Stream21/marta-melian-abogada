import { useEffect, useMemo, useRef, useState } from 'react';
import { FileText, Loader2 } from 'lucide-react';
import { api } from '@/api/client';
import type { BloqueEscrito } from '@/lib/hoja-encargo-variables';
import { cn } from '@/lib/utils';

interface EscritoPdfPreviewProps {
  tramiteId: string;
  tipo: string;
  bloques: BloqueEscrito[];
  incluirMembrete: boolean;
}

function pdfViewerSrc(blobUrl: string): string {
  return `${blobUrl}#view=FitH&toolbar=0&navpanes=0&scrollbar=1`;
}

export function EscritoPdfPreview({
  tramiteId,
  tipo,
  bloques,
  incluirMembrete,
}: EscritoPdfPreviewProps) {
  const [pdfUrl, setPdfUrl] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [iframeReady, setIframeReady] = useState(false);
  const currentUrlRef = useRef<string | null>(null);
  const bloquesKey = useMemo(() => JSON.stringify(bloques), [bloques]);

  useEffect(() => {
    let cancelled = false;
    setIframeReady(false);

    const timer = window.setTimeout(async () => {
      setLoading(true);
      setError(null);

      try {
        const blob = await api.downloadTramiteEscritoPdf(tramiteId, tipo, bloques, incluirMembrete);
        if (cancelled) return;

        const url = URL.createObjectURL(blob);
        if (currentUrlRef.current) {
          URL.revokeObjectURL(currentUrlRef.current);
        }
        currentUrlRef.current = url;
        setPdfUrl(url);
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'No se pudo generar la vista previa');
          setPdfUrl(null);
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    }, 350);

    return () => {
      cancelled = true;
      window.clearTimeout(timer);
    };
  }, [tramiteId, tipo, bloquesKey, incluirMembrete]);

  useEffect(() => {
    return () => {
      if (currentUrlRef.current) {
        URL.revokeObjectURL(currentUrlRef.current);
      }
    };
  }, []);

  if (error) {
    return (
      <p className="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
        {error}
      </p>
    );
  }

  const showOverlay = loading || (pdfUrl && !iframeReady);

  return (
    <div className="escrito-pdf-preview mx-auto w-full max-w-[820px]">
      <div className="mb-3 flex items-center justify-between gap-3 rounded-lg border border-border bg-card px-4 py-2.5 shadow-sm">
        <div className="flex min-w-0 items-center gap-2 text-sm text-foreground">
          <FileText className="h-4 w-4 shrink-0 text-primary" aria-hidden />
          <span className="font-medium">Vista previa del PDF</span>
          <span className="hidden text-muted-foreground sm:inline">— igual que descarga e impresión</span>
        </div>
        {loading && (
          <span className="flex shrink-0 items-center gap-1.5 text-xs text-muted-foreground">
            <Loader2 className="h-3.5 w-3.5 animate-spin" aria-hidden />
            Actualizando…
          </span>
        )}
      </div>

      <div className="escrito-pdf-preview-desk rounded-xl p-4 sm:p-6">
        <div
          className={cn(
            'escrito-pdf-preview-paper relative overflow-hidden rounded-sm bg-white shadow-[0_8px_30px_rgba(0,0,0,0.12)] ring-1 ring-black/5',
            showOverlay && 'min-h-[70vh]',
          )}
        >
          {showOverlay && (
            <div className="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-white/90 backdrop-blur-[1px]">
              <Loader2 className="h-8 w-8 animate-spin text-primary" aria-hidden />
              <p className="text-sm text-muted-foreground">
                {pdfUrl ? 'Renderizando páginas…' : 'Generando documento…'}
              </p>
            </div>
          )}

          {pdfUrl ? (
            <iframe
              key={pdfUrl}
              title="Vista previa del documento PDF"
              src={pdfViewerSrc(pdfUrl)}
              className="block min-h-[70vh] w-full border-0 bg-white"
              onLoad={() => setIframeReady(true)}
            />
          ) : (
            <div className="flex min-h-[70vh] items-center justify-center bg-white">
              <p className="text-sm text-muted-foreground">Preparando documento…</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
