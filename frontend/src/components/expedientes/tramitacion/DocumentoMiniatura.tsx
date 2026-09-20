import { useEffect, useId, useState } from 'react';
import { Eye, FileText, Loader2 } from 'lucide-react';
import {
  fetchAccesoPdf,
  fetchAuthenticatedBlob,
} from '@/api/client';
import { ContratacionPdfEmbed } from '@/components/expedientes/contratacion/ContratacionPdfEmbed';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { renderPdfThumbnail } from '@/lib/pdf-viewer';
import { cn } from '@/lib/utils';

interface DocumentoMiniaturaProps {
  url: string;
  title: string;
  subtitle?: string;
  badge?: string;
  /** Portal del cliente: el documento no usa JWT. */
  publicAccess?: boolean;
  className?: string;
}

function isImageMime(type: string, url: string): boolean {
  if (type.startsWith('image/')) return true;
  return /\.(png|jpe?g|webp|gif)(\?|$)/i.test(url);
}

function isPdfMime(type: string, url: string): boolean {
  if (type === 'application/pdf' || type === 'application/x-pdf') return true;
  return /\.pdf(\?|$)/i.test(url) || url.includes('/oficio');
}

export function DocumentoMiniatura({
  url,
  title,
  subtitle,
  badge,
  publicAccess = false,
  className,
}: DocumentoMiniaturaProps) {
  const titleId = useId();
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(true);
  const [thumb, setThumb] = useState<string | null>(null);
  const [kind, setKind] = useState<'pdf' | 'image' | 'other'>('other');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    let objectUrl: string | null = null;
    let thumbUrl: string | null = null;

    setLoading(true);
    setError(null);
    setThumb(null);

    void (async () => {
      try {
        if (publicAccess) {
          const blobUrl = await fetchAccesoPdf(url);
          objectUrl = blobUrl;
          const looksPdf = isPdfMime('', url);
          if (looksPdf) {
            const dataUrl = await renderPdfThumbnail(blobUrl);
            if (cancelled) return;
            setKind('pdf');
            setThumb(dataUrl);
            return;
          }
          if (cancelled) return;
          setKind('image');
          setThumb(blobUrl);
          objectUrl = null;
          thumbUrl = blobUrl;
          return;
        }

        const blob = await fetchAuthenticatedBlob(url);
        const blobUrl = URL.createObjectURL(blob);
        objectUrl = blobUrl;
        if (isPdfMime(blob.type, url)) {
          const dataUrl = await renderPdfThumbnail(blobUrl);
          if (cancelled) return;
          setKind('pdf');
          setThumb(dataUrl);
          return;
        }
        if (isImageMime(blob.type, url)) {
          if (cancelled) return;
          setKind('image');
          setThumb(blobUrl);
          objectUrl = null;
          thumbUrl = blobUrl;
          return;
        }
        if (cancelled) return;
        setKind('other');
      } catch {
        if (!cancelled) setError('No se pudo generar la vista previa.');
      } finally {
        if (!cancelled) setLoading(false);
        if (objectUrl) URL.revokeObjectURL(objectUrl);
      }
    })();

    return () => {
      cancelled = true;
      if (thumbUrl) URL.revokeObjectURL(thumbUrl);
    };
  }, [url, publicAccess]);

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        aria-labelledby={titleId}
        className={cn(
          'group flex w-full cursor-pointer flex-col overflow-hidden rounded-xl border border-border bg-card text-left shadow-sm transition-shadow hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
          className,
        )}
      >
        <div className="relative aspect-[3/4] overflow-hidden bg-muted">
          {loading ? (
            <div className="flex h-full items-center justify-center">
              <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
            </div>
          ) : thumb ? (
            <img
              src={thumb}
              alt=""
              className="h-full w-full object-cover object-top transition-transform duration-200 group-hover:scale-[1.02]"
            />
          ) : (
            <div className="flex h-full flex-col items-center justify-center gap-2 p-3 text-muted-foreground">
              <FileText className="h-8 w-8" />
              <span className="text-center text-xs">{error ?? 'Sin vista previa'}</span>
            </div>
          )}
          <div className="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-center bg-gradient-to-t from-black/55 to-transparent py-2 opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">
            <span className="inline-flex items-center gap-1 rounded-full bg-card/95 px-2 py-1 text-[11px] font-medium text-foreground">
              <Eye className="h-3 w-3" />
              Ver
            </span>
          </div>
        </div>
        <div className="space-y-1 p-3">
          <div className="flex items-start justify-between gap-2">
            <p id={titleId} className="line-clamp-2 text-sm font-medium text-foreground">
              {title}
            </p>
            {badge ? (
              <Badge variant="outline" className="shrink-0">
                {badge}
              </Badge>
            ) : null}
          </div>
          {subtitle ? <p className="line-clamp-2 text-xs text-muted-foreground">{subtitle}</p> : null}
        </div>
      </button>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-h-[90vh] max-w-3xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{title}</DialogTitle>
            <DialogDescription>
              {subtitle ?? 'Vista previa del documento. No es necesario salir de esta pestaña.'}
            </DialogDescription>
          </DialogHeader>
          {kind === 'image' && thumb ? (
            <img src={thumb} alt={title} className="max-h-[70vh] w-full rounded-lg object-contain" />
          ) : publicAccess ? (
            <PublicPdfEmbed url={url} title={title} />
          ) : (
            <ContratacionPdfEmbed url={url} title={title} className="h-[70vh] min-h-[70vh]" />
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}

function PublicPdfEmbed({ url, title }: { url: string; title: string }) {
  const [blobUrl, setBlobUrl] = useState<string | null>(null);

  useEffect(() => {
    let revoked: string | null = null;
    void fetchAccesoPdf(url).then((objectUrl) => {
      revoked = objectUrl;
      setBlobUrl(objectUrl);
    });
    return () => {
      if (revoked) URL.revokeObjectURL(revoked);
    };
  }, [url]);

  if (!blobUrl) {
    return (
      <div className="flex min-h-[320px] items-center justify-center rounded-lg border bg-muted/30">
        <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return <iframe title={title} src={blobUrl} className="h-[70vh] min-h-[320px] w-full rounded-lg border bg-white" />;
}

export function DocumentoMiniaturaPlaceholder({
  title,
  subtitle,
  onSelectFile,
  pending,
  inputId,
}: {
  title: string;
  subtitle: string;
  onSelectFile: (file: File) => void;
  pending?: boolean;
  inputId?: string;
}) {
  const generatedId = useId();
  const id = inputId ?? generatedId;

  return (
    <div className="flex min-h-[220px] flex-col overflow-hidden rounded-xl border border-dashed border-primary/30 bg-primary/5">
      <div className="flex flex-1 flex-col items-center justify-center gap-2 px-4 py-6 text-center">
        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
          {pending ? <Loader2 className="h-5 w-5 animate-spin" /> : <FileText className="h-5 w-5" />}
        </div>
        <p className="text-sm font-medium text-foreground">{title}</p>
        <p className="text-xs text-muted-foreground">{subtitle}</p>
      </div>
      <div className="border-t border-border/60 bg-card/80 p-3">
        <input
          id={id}
          type="file"
          accept=".pdf,application/pdf"
          className="sr-only"
          disabled={pending}
          onChange={(e) => {
            const file = e.target.files?.[0];
            if (file) onSelectFile(file);
            e.target.value = '';
          }}
        />
        <Button size="sm" className="w-full" disabled={pending} asChild>
          <label htmlFor={id} className="cursor-pointer">
            Adjuntar PDF
          </label>
        </Button>
      </div>
    </div>
  );
}
