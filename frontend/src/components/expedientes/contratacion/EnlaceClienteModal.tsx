import { useState } from 'react';
import { Copy, ExternalLink, Link2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

interface EnlaceClienteModalProps {
  accessUrl: string | null;
}

export function EnlaceClienteModal({ accessUrl }: EnlaceClienteModalProps) {
  const [copiado, setCopiado] = useState(false);

  if (!accessUrl) {
    return null;
  }

  const copiar = async () => {
    await navigator.clipboard.writeText(accessUrl);
    setCopiado(true);
    setTimeout(() => setCopiado(false), 2000);
  };

  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <Link2 className="mr-2 h-4 w-4" />
          Enlace del cliente
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Enlace de acceso del cliente</DialogTitle>
          <DialogDescription>
            Comparta este enlace con el cliente. Podrá revisar documentos, firmas y pago antes de
            confirmar cada paso. Usted recibirá las actualizaciones en tiempo real.
          </DialogDescription>
        </DialogHeader>

        <div className="flex gap-2">
          <Input readOnly value={accessUrl} className="font-mono text-xs" />
          <Button type="button" variant="outline" onClick={() => void copiar()}>
            <Copy className="h-4 w-4" />
          </Button>
        </div>

        {copiado && <p className="text-sm text-emerald-600">Enlace copiado al portapapeles.</p>}

        <div className="flex gap-2 pt-2">
          <Button asChild className="flex-1">
            <a href={accessUrl} target="_blank" rel="noopener noreferrer">
              <ExternalLink className="mr-2 h-4 w-4" />
              Abrir vista del cliente
            </a>
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
