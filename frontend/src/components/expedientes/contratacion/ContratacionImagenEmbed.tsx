import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import { fetchAuthenticatedAsset } from '@/api/client';

interface ContratacionImagenEmbedProps {
  url: string;
  title: string;
}

export function ContratacionImagenEmbed({ url, title }: ContratacionImagenEmbedProps) {
  const [blobUrl, setBlobUrl] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let revoked: string | null = null;
    setLoading(true);

    void fetchAuthenticatedAsset(url)
      .then((objectUrl) => {
        revoked = objectUrl;
        setBlobUrl(objectUrl);
      })
      .finally(() => setLoading(false));

    return () => {
      if (revoked) URL.revokeObjectURL(revoked);
    };
  }, [url]);

  if (loading) {
    return (
      <div className="flex h-48 items-center justify-center rounded-lg border bg-muted/30">
        <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!blobUrl) {
    return <p className="text-sm text-muted-foreground">Imagen no disponible.</p>;
  }

  return (
    <img
      src={blobUrl}
      alt={title}
      className="max-h-64 w-full rounded-lg border object-contain bg-white"
    />
  );
}
