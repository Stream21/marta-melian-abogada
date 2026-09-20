import { useEffect, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Loader2, Mail, MessageCircle, Settings } from 'lucide-react';
import { api, type ExpedienteResponse } from '@/api/client';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

type Canal = 'whatsapp' | 'email';

interface ExpedienteConfiguracionModalProps {
  expediente: ExpedienteResponse;
}

export function ExpedienteConfiguracionModal({ expediente }: ExpedienteConfiguracionModalProps) {
  const queryClient = useQueryClient();
  const [open, setOpen] = useState(false);
  const [whatsapp, setWhatsapp] = useState(false);
  const [email, setEmail] = useState(false);

  const tieneTelefono = Boolean(expediente.clienteTieneTelefono);
  const tieneEmail = Boolean(expediente.clienteTieneEmail);
  const soloLectura = expediente.estado !== 'abierto';

  useEffect(() => {
    if (!open) {
      return;
    }
    const actuales = expediente.canalesNotificacion ?? [];
    if (actuales.length > 0) {
      setWhatsapp(actuales.includes('whatsapp') && tieneTelefono);
      setEmail(actuales.includes('email') && tieneEmail);
      return;
    }
    setWhatsapp(tieneTelefono);
    setEmail(tieneEmail);
  }, [open, expediente.canalesNotificacion, tieneTelefono, tieneEmail]);

  const mutation = useMutation({
    mutationFn: (canales: Canal[]) => api.actualizarCanalesNotificacion(expediente.id, canales),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['expediente', expediente.id] });
      void queryClient.invalidateQueries({ queryKey: ['auditoria', expediente.id] });
      setOpen(false);
    },
  });

  const seleccionados =
    (whatsapp && tieneTelefono ? 1 : 0) + (email && tieneEmail ? 1 : 0);

  const toggle = (canal: Canal) => {
    if (soloLectura) {
      return;
    }
    if (canal === 'whatsapp') {
      if (!tieneTelefono) {
        return;
      }
      if (whatsapp && seleccionados <= 1) {
        return;
      }
      setWhatsapp(!whatsapp);
      return;
    }
    if (!tieneEmail) {
      return;
    }
    if (email && seleccionados <= 1) {
      return;
    }
    setEmail(!email);
  };

  const guardar = () => {
    const canales: Canal[] = [
      ...(whatsapp && tieneTelefono ? (['whatsapp'] as const) : []),
      ...(email && tieneEmail ? (['email'] as const) : []),
    ];
    mutation.mutate([...canales]);
  };

  const canalesUi: Array<{
    id: Canal;
    label: string;
    desc: string;
    icon: typeof MessageCircle;
    disponible: boolean;
    activo: boolean;
  }> = [
    {
      id: 'whatsapp',
      label: 'WhatsApp',
      desc: tieneTelefono
        ? 'Avisos al teléfono del cliente.'
        : 'El cliente no tiene teléfono registrado.',
      icon: MessageCircle,
      disponible: tieneTelefono,
      activo: whatsapp,
    },
    {
      id: 'email',
      label: 'Correo electrónico',
      desc: tieneEmail
        ? 'Avisos al correo del cliente.'
        : 'El cliente no tiene email registrado.',
      icon: Mail,
      disponible: tieneEmail,
      activo: email,
    },
  ];

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="h-8"
          title="Configuración del expediente"
          aria-label="Configuración del expediente"
        >
          <Settings className="h-4 w-4" />
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Configuración del expediente</DialogTitle>
          <DialogDescription>
            Elija por qué canales se avisará automáticamente al cliente (cambios de fase y
            notificaciones del despacho). Puede modificarlo en cualquier momento.
          </DialogDescription>
        </DialogHeader>

        <div className="grid gap-3">
          {canalesUi.map((canal) => {
            const Icon = canal.icon;
            const esUnico = canal.activo && seleccionados === 1 && canal.disponible;

            return (
              <button
                key={canal.id}
                type="button"
                onClick={() => toggle(canal.id)}
                disabled={soloLectura || !canal.disponible || esUnico}
                className={cn(
                  'flex items-start gap-4 rounded-lg border-2 p-4 text-left transition-colors',
                  canal.activo && canal.disponible
                    ? 'border-primary bg-primary/5'
                    : 'border-border bg-card',
                  !canal.disponible && 'opacity-60',
                  !soloLectura && canal.disponible && !esUnico && 'hover:border-primary/30',
                  (soloLectura || !canal.disponible || esUnico) && 'cursor-not-allowed',
                )}
              >
                <div
                  className={cn(
                    'rounded-lg p-2',
                    canal.activo && canal.disponible
                      ? 'bg-primary/10 text-primary'
                      : 'bg-muted text-muted-foreground',
                  )}
                >
                  <Icon className="h-5 w-5" />
                </div>
                <div>
                  <p className="font-semibold">{canal.label}</p>
                  <p className="mt-1 text-sm text-muted-foreground">{canal.desc}</p>
                </div>
              </button>
            );
          })}
        </div>

        {soloLectura && (
          <p className="text-sm text-amber-800">
            El expediente no está abierto; no se pueden cambiar los canales.
          </p>
        )}

        {mutation.error instanceof Error && (
          <p className="text-sm text-destructive">{mutation.error.message}</p>
        )}

        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => setOpen(false)}>
            Cancelar
          </Button>
          <Button
            type="button"
            disabled={soloLectura || seleccionados < 1 || mutation.isPending}
            onClick={guardar}
          >
            {mutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Guardar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
