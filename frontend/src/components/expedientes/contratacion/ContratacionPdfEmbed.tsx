import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import { fetchAuthenticatedAsset } from '@/api/client';

interface ContratacionPdfEmbedProps {
  url: string;
  title: string;
  className?: string;
}

export function ContratacionPdfEmbed({ url, title, className }: ContratacionPdfEmbedProps) {
  const [blobUrl, setBlobUrl] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let revoked: string | null = null;
    setLoading(true);
    setError(null);

    void fetchAuthenticatedAsset(url)
      .then((objectUrl) => {
        if (!objectUrl) {
          setError('No se pudo cargar el documento.');
          return;
        }
        revoked = objectUrl;
        setBlobUrl(objectUrl);
      })
      .catch(() => setError('No se pudo cargar el documento.'))
      .finally(() => setLoading(false));

    return () => {
      if (revoked) URL.revokeObjectURL(revoked);
    };
  }, [url]);

  if (loading) {
    return (
      <div className={`flex items-center justify-center rounded-lg border bg-muted/30 p-8 ${className ?? ''}`}>
        <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error || !blobUrl) {
    return (
      <div className={`rounded-lg border bg-muted/30 p-4 text-sm text-destructive ${className ?? ''}`}>
        {error ?? 'Documento no disponible.'}
      </div>
    );
  }

  return (
    <iframe
      title={title}
      src={blobUrl}
      className={`w-full rounded-lg border bg-white min-h-[320px] ${className ?? 'h-[420px]'}`}
    />
  );
}
