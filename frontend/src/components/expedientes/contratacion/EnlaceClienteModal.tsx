import { useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
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
import { mensajeCanalesEnviados } from '@/lib/email-notificacion';
import { cn } from '@/lib/utils';

interface EnlaceClienteModalProps {
  expedienteId: string;
  accessUrl: string | null;
}

export function EnlaceClienteModal({ expedienteId, accessUrl }: EnlaceClienteModalProps) {
  const [copiado, setCopiado] = useState(false);
  const [mensajeEnvio, setMensajeEnvio] = useState<string | null>(null);
  const [envioConError, setEnvioConError] = useState(false);

  const { data: emailEstado } = useQuery({
    queryKey: ['email-estado'],
    queryFn: () => api.getEmailEstado(),
  });

  const enviarMutation = useMutation({
    mutationFn: (canales: ('whatsapp' | 'email')[]) => api.enviarEnlaceExpediente(expedienteId, canales),
    onSuccess: (data) => {
      const mensaje = mensajeCanalesEnviados(data.canalesEnviados, emailEstado?.bandejaUrl);
      setMensajeEnvio(mensaje);
      setEnvioConError(data.canalesEnviados.some((c) => c.endsWith('_error')));
    },
    onError: (err: Error) => {
      setMensajeEnvio(err.message);
      setEnvioConError(true);
    },
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
            {emailEstado?.capturaLocal && emailEstado.bandejaUrl && (
              <>
                {' '}
                En desarrollo los correos no llegan al buzón real: consúltelos en{' '}
                <a
                  href={emailEstado.bandejaUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-primary underline"
                >
                  Mailpit
                </a>
                .
              </>
            )}
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

        {mensajeEnvio && (
          <p
            className={cn(
              'text-sm',
              envioConError ? 'text-destructive' : 'text-emerald-700',
            )}
          >
            {mensajeEnvio}
          </p>
        )}

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
