import { useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { ChevronDown, Loader2, Mail } from 'lucide-react';
import { api } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export function EmailIntegracionPanel() {
  const [email, setEmail] = useState('');
  const [asunto, setAsunto] = useState('');
  const [mensaje, setMensaje] = useState('');
  const [resultado, setResultado] = useState<string | null>(null);

  const { data: estado } = useQuery({
    queryKey: ['email-estado'],
    queryFn: () => api.getEmailEstado(),
  });

  const probarMutation = useMutation({
    mutationFn: () =>
      api.probarEmail({
        email,
        asunto: asunto || undefined,
        mensaje: mensaje || undefined,
      }),
    onSuccess: () => {
      setResultado(
        estado?.capturaLocal && estado.bandejaUrl
          ? `Correo de prueba enviado. En desarrollo consúltelo en ${estado.bandejaUrl}`
          : 'Correo de prueba enviado correctamente.',
      );
    },
    onError: (err: Error) => setResultado(err.message),
  });

  return (
    <details className="group rounded-lg border border-border bg-muted/20">
      <summary className="flex cursor-pointer list-none items-center justify-between gap-2 p-5 font-medium text-foreground [&::-webkit-details-marker]:hidden">
        <span className="flex items-center gap-2">
          <Mail className="h-4 w-4 text-primary" />
          Integración correo (SMTP)
        </span>
        <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" />
      </summary>
      <div className="border-t border-border px-5 pb-5 pt-4 space-y-4">
        <div className="flex flex-wrap gap-2 text-xs">
          <span
            className={cn(
              'inline-flex items-center rounded-full px-2.5 py-0.5 font-medium',
              estado?.configurado ? 'bg-emerald-100 text-emerald-800' : 'bg-muted text-muted-foreground',
            )}
          >
            Correo: {estado?.configurado ? 'Configurado' : 'No configurado'}
          </span>
          {estado?.capturaLocal && estado.bandejaUrl && (
            <span className="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 font-medium text-blue-800">
              Modo desarrollo (Mailpit)
            </span>
          )}
        </div>
        <p className="text-sm text-muted-foreground">
          Notificaciones al cliente (alta de expediente, enlace de acceso). En desarrollo use Mailpit
          (http://localhost:8025) con MAILER_DSN=smtp://mailpit:1025.
        </p>
        <div className="grid max-w-xl gap-4">
          <div className="space-y-1.5">
            <Label htmlFor="email-destino">Email destino</Label>
            <Input
              id="email-destino"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="cliente@email.com"
            />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="email-asunto">Asunto (opcional)</Label>
            <Input
              id="email-asunto"
              value={asunto}
              onChange={(e) => setAsunto(e.target.value)}
              placeholder="Prueba Bufete Melián"
            />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="email-mensaje">Mensaje (opcional)</Label>
            <Input
              id="email-mensaje"
              value={mensaje}
              onChange={(e) => setMensaje(e.target.value)}
              placeholder="Texto del correo de prueba"
            />
          </div>
        </div>
        <Button
          type="button"
          variant="outline"
          disabled={!email || probarMutation.isPending || !estado?.configurado}
          onClick={() => probarMutation.mutate()}
        >
          {probarMutation.isPending ? (
            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
          ) : (
            <Mail className="mr-2 h-4 w-4" />
          )}
          Probar correo
        </Button>
        {resultado && (
          <p className={cn('text-sm', resultado.includes('enviado') ? 'text-emerald-700' : 'text-destructive')}>
            {resultado}
          </p>
        )}
      </div>
    </details>
  );
}
