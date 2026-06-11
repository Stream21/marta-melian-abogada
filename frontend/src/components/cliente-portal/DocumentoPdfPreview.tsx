import { useEffect, useState } from 'react';
import { Eye, Loader2 } from 'lucide-react';
import { fetchAccesoPdf } from '@/api/client';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';

interface DocumentoPdfPreviewProps {
  label: string;
  previewUrl: string;
  onViewed?: () => void;
  viewed?: boolean;
}

export function DocumentoPdfPreview({ label, previewUrl, onViewed, viewed }: DocumentoPdfPreviewProps) {
  const [open, setOpen] = useState(false);
  const [pdfUrl, setPdfUrl] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!open) {
      if (pdfUrl) URL.revokeObjectURL(pdfUrl);
      setPdfUrl(null);
      setError(null);
      return;
    }

    let revoked: string | null = null;
    setLoading(true);
    setError(null);

    void fetchAccesoPdf(previewUrl)
      .then((url) => {
        revoked = url;
        setPdfUrl(url);
        onViewed?.();
      })
      .catch((err: Error) => setError(err.message))
      .finally(() => setLoading(false));

    return () => {
      if (revoked) URL.revokeObjectURL(revoked);
    };
  }, [open, previewUrl, onViewed]);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm" className="w-full justify-start">
          <Eye className="mr-2 h-4 w-4" />
          Ver {label}
          {viewed && <span className="ml-auto text-xs text-emerald-600">Revisado</span>}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-w-4xl h-[85vh] flex flex-col">
        <DialogHeader>
          <DialogTitle>{label}</DialogTitle>
          <DialogDescription>
            Revise el documento con atención. Si todo es correcto, confirme el paso al cerrar esta ventana.
          </DialogDescription>
        </DialogHeader>
        <div className="flex-1 min-h-0 rounded-lg border bg-muted/30">
          {loading && (
            <div className="flex h-full items-center justify-center text-muted-foreground">
              <Loader2 className="mr-2 h-5 w-5 animate-spin" />
              Generando vista previa…
            </div>
          )}
          {error && (
            <div className="flex h-full items-center justify-center p-6 text-destructive text-sm">{error}</div>
          )}
          {pdfUrl && !loading && (
            <iframe title={label} src={pdfUrl} className="h-full w-full rounded-lg" />
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
