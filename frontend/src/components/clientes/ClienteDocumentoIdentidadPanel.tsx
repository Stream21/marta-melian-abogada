import { useEffect, useState } from 'react';
import { IdCard } from 'lucide-react';
import type { ClienteResponse } from '@/api/client';
import { fetchAuthenticatedAsset } from '@/api/client';

interface ClienteDocumentoIdentidadPanelProps {
  cliente: ClienteResponse;
}

export function ClienteDocumentoIdentidadPanel({ cliente }: ClienteDocumentoIdentidadPanelProps) {
  const doc = cliente.documentoIdentidad;
  const [anversoUrl, setAnversoUrl] = useState<string | null>(null);
  const [reversoUrl, setReversoUrl] = useState<string | null>(null);

  useEffect(() => {
    let anversoObjectUrl: string | null = null;
    let reversoObjectUrl: string | null = null;

    const load = async () => {
      if (doc?.anversoUrl) {
        anversoObjectUrl = await fetchAuthenticatedAsset(doc.anversoUrl, doc.escaneadoAt ?? undefined);
        setAnversoUrl(anversoObjectUrl);
      }
      if (doc?.reversoUrl) {
        reversoObjectUrl = await fetchAuthenticatedAsset(doc.reversoUrl, doc.escaneadoAt ?? undefined);
        setReversoUrl(reversoObjectUrl);
      }
    };

    void load();

    return () => {
      if (anversoObjectUrl) URL.revokeObjectURL(anversoObjectUrl);
      if (reversoObjectUrl) URL.revokeObjectURL(reversoObjectUrl);
    };
  }, [doc?.anversoUrl, doc?.reversoUrl, doc?.escaneadoAt]);

  if (!doc?.tieneAnverso) {
    return (
      <div className="panel">
        <div className="p-6 text-sm text-muted-foreground">
          No hay documento de identidad registrado para este cliente.
        </div>
      </div>
    );
  }

  return (
    <div className="panel">
      <div className="panel-header">
        <div className="panel-header-icon">
          <IdCard className="h-5 w-5" />
        </div>
        <div>
          <h2 className="panel-title">Documento de identidad</h2>
          <p className="text-sm text-muted-foreground">
            {doc.tipoEscaneoLabel ?? 'Documento escaneado'}
            {doc.escaneadoAt
              ? ` · ${new Date(doc.escaneadoAt).toLocaleString('es-ES')}`
              : ''}
          </p>
        </div>
      </div>

      <div className="grid gap-4 p-6 md:grid-cols-2">
        <ImagenDocumento label={doc.tipoEscaneo === 'pasaporte' ? 'Página de datos' : 'Anverso'} url={anversoUrl} />
        {doc.tieneReverso && <ImagenDocumento label="Reverso" url={reversoUrl} />}
      </div>
    </div>
  );
}

function ImagenDocumento({ label, url }: { label: string; url: string | null }) {
  return (
    <div className="space-y-2">
      <p className="section-label">{label}</p>
      <div className="flex min-h-[200px] items-center justify-center rounded-lg border border-border bg-muted/30 p-4">
        {url ? (
          <img src={url} alt={label} className="max-h-64 max-w-full rounded object-contain" />
        ) : (
          <p className="text-sm text-muted-foreground">Cargando imagen…</p>
        )}
      </div>
    </div>
  );
}
