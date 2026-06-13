import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { Copy, ExternalLink, Link2, Loader2, Mail, MessageCircle } from 'lucide-react';
import { api } from '@/api/client';
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
  expedienteId: string;
  accessUrl: string | null;
}

export function EnlaceClienteModal({ expedienteId, accessUrl }: EnlaceClienteModalProps) {
  const [copiado, setCopiado] = useState(false);
  const [mensajeEnvio, setMensajeEnvio] = useState<string | null>(null);

  const enviarMutation = useMutation({
    mutationFn: (canales: ('whatsapp' | 'email')[]) => api.enviarEnlaceExpediente(expedienteId, canales),
    onSuccess: (data) => {
      const ok = data.canalesEnviados.filter((c) => !c.endsWith('_error'));
      const err = data.canalesEnviados.filter((c) => c.endsWith('_error'));
      if (ok.length > 0) {
        setMensajeEnvio(`Enviado por: ${ok.join(', ')}`);
      } else if (err.length > 0) {
        setMensajeEnvio('No se pudo enviar. Revise la configuración Twilio o el email del cliente.');
      }
    },
    onError: (err: Error) => setMensajeEnvio(err.message),
  });

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
            Comparta el enlace por copiar/pegar o envíelo por WhatsApp o email. El SMS se reserva para
            el código OTP al firmar documentos.
          </DialogDescription>
        </DialogHeader>

        <div className="flex gap-2">
          <Input readOnly value={accessUrl} className="font-mono text-xs" />
          <Button type="button" variant="outline" onClick={() => void copiar()}>
            <Copy className="h-4 w-4" />
          </Button>
        </div>

        {copiado && <p className="text-sm text-emerald-600">Enlace copiado al portapapeles.</p>}

        <div className="flex flex-wrap gap-2 pt-2">
          <Button
            type="button"
            variant="outline"
            className="flex-1 min-w-[120px]"
            disabled={enviarMutation.isPending}
            onClick={() => enviarMutation.mutate(['whatsapp'])}
          >
            {enviarMutation.isPending && enviarMutation.variables?.includes('whatsapp') ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <MessageCircle className="mr-2 h-4 w-4" />
            )}
            Enviar WhatsApp
          </Button>
          <Button
            type="button"
            variant="outline"
            className="flex-1 min-w-[120px]"
            disabled={enviarMutation.isPending}
            onClick={() => enviarMutation.mutate(['email'])}
          >
            {enviarMutation.isPending && enviarMutation.variables?.includes('email') ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <Mail className="mr-2 h-4 w-4" />
            )}
            Enviar email
          </Button>
        </div>

        {mensajeEnvio && <p className="text-sm text-muted-foreground">{mensajeEnvio}</p>}

        <Button asChild className="w-full">
          <a href={accessUrl} target="_blank" rel="noopener noreferrer">
            <ExternalLink className="mr-2 h-4 w-4" />
            Abrir vista del cliente
          </a>
        </Button>
      </DialogContent>
    </Dialog>
  );
}
