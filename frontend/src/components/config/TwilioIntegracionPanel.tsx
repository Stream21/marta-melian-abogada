import { useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { ChevronDown, Loader2, MessageCircle, Smartphone } from 'lucide-react';
import { api } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export function TwilioIntegracionPanel() {
  const [telefono, setTelefono] = useState('');
  const [mensaje, setMensaje] = useState('');
  const [resultado, setResultado] = useState<string | null>(null);

  const { data: estado } = useQuery({
    queryKey: ['twilio-estado'],
    queryFn: () => api.getTwilioEstado(),
  });

  const probarMutation = useMutation({
    mutationFn: (canal: 'sms' | 'whatsapp') =>
      api.probarTwilio({ canal, telefono, mensaje: mensaje || undefined }),
    onSuccess: (data) => {
      setResultado(`Mensaje de prueba enviado por ${data.canal === 'sms' ? 'SMS' : 'WhatsApp'}.`);
    },
    onError: (err: Error) => setResultado(err.message),
  });

  return (
    <details className="group rounded-lg border border-border bg-muted/20">
      <summary className="flex cursor-pointer list-none items-center justify-between gap-2 p-5 font-medium text-foreground [&::-webkit-details-marker]:hidden">
        <span className="flex items-center gap-2">
          <MessageCircle className="h-4 w-4 text-primary" />
          Integración Twilio (OTP SMS y WhatsApp)
        </span>
        <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" />
      </summary>
      <div className="border-t border-border px-5 pb-5 pt-4 space-y-4">
        <div className="flex flex-wrap gap-2 text-xs">
          <BadgeEstado label="SMS" ok={estado?.sms} />
          <BadgeEstado label="WhatsApp" ok={estado?.whatsapp} />
        </div>
        <p className="text-sm text-muted-foreground">
          SMS: reservado al OTP de firma. WhatsApp: notificaciones al cliente. Configure TWILIO_SMS_FROM y
          TWILIO_WHATSAPP_FROM en el entorno.
        </p>
        <div className="grid gap-4 sm:grid-cols-2 max-w-xl">
          <div className="space-y-1.5 sm:col-span-2">
            <Label htmlFor="twilio-telefono">Teléfono destino (+34…)</Label>
            <Input
              id="twilio-telefono"
              value={telefono}
              onChange={(e) => setTelefono(e.target.value)}
              placeholder="+34600000000"
            />
          </div>
          <div className="space-y-1.5 sm:col-span-2">
            <Label htmlFor="twilio-mensaje">Mensaje (opcional)</Label>
            <Input
              id="twilio-mensaje"
              value={mensaje}
              onChange={(e) => setMensaje(e.target.value)}
              placeholder="Prueba Bufete Melián"
            />
          </div>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button
            type="button"
            variant="outline"
            disabled={!telefono || probarMutation.isPending || !estado?.sms}
            onClick={() => probarMutation.mutate('sms')}
          >
            {probarMutation.isPending && probarMutation.variables === 'sms' ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <Smartphone className="mr-2 h-4 w-4" />
            )}
            Probar SMS (OTP)
          </Button>
          <Button
            type="button"
            variant="outline"
            disabled={!telefono || probarMutation.isPending || !estado?.whatsapp}
            onClick={() => probarMutation.mutate('whatsapp')}
          >
            {probarMutation.isPending && probarMutation.variables === 'whatsapp' ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <MessageCircle className="mr-2 h-4 w-4" />
            )}
            Probar WhatsApp (notificaciones)
          </Button>
        </div>
        {resultado && (
          <p className={cn('text-sm', resultado.includes('enviado') ? 'text-emerald-700' : 'text-destructive')}>
            {resultado}
          </p>
        )}
      </div>
    </details>
  );
}

function BadgeEstado({ label, ok }: { label: string; ok?: boolean }) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full px-2.5 py-0.5 font-medium',
        ok ? 'bg-emerald-100 text-emerald-800' : 'bg-muted text-muted-foreground',
      )}
    >
      {label}: {ok ? 'Configurado' : 'No configurado'}
    </span>
  );
}
