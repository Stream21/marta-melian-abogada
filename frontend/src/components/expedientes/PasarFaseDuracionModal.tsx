import { useEffect, useMemo, useState } from 'react';
import { CalendarClock, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const DIAS_DEFAULT = 30;

function formatFechaIso(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function addDays(base: Date, days: number): Date {
  const next = new Date(base);
  next.setHours(12, 0, 0, 0);
  next.setDate(next.getDate() + days);
  return next;
}

function daysBetween(fromIso: string, toIso: string): number {
  const from = new Date(`${fromIso}T12:00:00`);
  const to = new Date(`${toIso}T12:00:00`);
  return Math.round((to.getTime() - from.getTime()) / (1000 * 60 * 60 * 24));
}

function formatFechaLarga(iso: string): string {
  try {
    return new Date(`${iso}T12:00:00`).toLocaleDateString('es-ES', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  } catch {
    return iso;
  }
}

export interface PasarFaseDuracionModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  faseDestinoLabel: string;
  faseDestinoNumero: 2 | 3 | 4;
  confirmLabel: string;
  pending?: boolean;
  error?: string | null;
  onConfirm: (fechaVencimientoFase: string) => void;
}

export function PasarFaseDuracionModal({
  open,
  onOpenChange,
  faseDestinoLabel,
  faseDestinoNumero,
  confirmLabel,
  pending = false,
  error = null,
  onConfirm,
}: PasarFaseDuracionModalProps) {
  const hoyIso = useMemo(() => formatFechaIso(new Date()), []);
  const [diasEspera, setDiasEspera] = useState(String(DIAS_DEFAULT));
  const [fechaLimite, setFechaLimite] = useState(() =>
    formatFechaIso(addDays(new Date(), DIAS_DEFAULT)),
  );

  useEffect(() => {
    if (!open) return;
    const defaultFecha = formatFechaIso(addDays(new Date(), DIAS_DEFAULT));
    setDiasEspera(String(DIAS_DEFAULT));
    setFechaLimite(defaultFecha);
  }, [open]);

  const diasParsed = Number.parseInt(diasEspera, 10);
  const diasValidos = Number.isFinite(diasParsed) && diasParsed >= 1 && diasParsed <= 365;
  const fechaValida = Boolean(fechaLimite) && fechaLimite >= hoyIso;

  const handleDiasChange = (value: string) => {
    setDiasEspera(value);
    const n = Number.parseInt(value, 10);
    if (Number.isFinite(n) && n >= 1 && n <= 365) {
      setFechaLimite(formatFechaIso(addDays(new Date(), n)));
    }
  };

  const handleFechaChange = (value: string) => {
    setFechaLimite(value);
    if (!value || value < hoyIso) return;
    setDiasEspera(String(Math.max(1, daysBetween(hoyIso, value))));
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <CalendarClock className="h-5 w-5 text-primary" />
            Pasar a Fase {faseDestinoNumero}
          </DialogTitle>
          <DialogDescription>
            Antes de avanzar a <span className="font-medium text-foreground">{faseDestinoLabel}</span>,
            indique la duración de espera (plazo) que tendrá la siguiente fase. Ese vencimiento se
            incorporará al circuito de fechas del expediente.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="dias-espera-fase">Días de espera</Label>
            <Input
              id="dias-espera-fase"
              type="number"
              min={1}
              max={365}
              value={diasEspera}
              onChange={(e) => handleDiasChange(e.target.value)}
              disabled={pending}
            />
            {!diasValidos && (
              <p className="text-xs text-muted-foreground">Indique entre 1 y 365 días.</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="fecha-limite-fase">Fecha límite de la siguiente fase</Label>
            <Input
              id="fecha-limite-fase"
              type="date"
              min={hoyIso}
              value={fechaLimite}
              onChange={(e) => handleFechaChange(e.target.value)}
              disabled={pending}
            />
            {fechaValida && (
              <p className="text-xs text-muted-foreground">
                Plazo hasta el {formatFechaLarga(fechaLimite)}
                {diasValidos ? ` (${diasParsed} día${diasParsed === 1 ? '' : 's'})` : ''}.
              </p>
            )}
          </div>
        </div>

        {error && <p className="text-sm text-destructive">{error}</p>}

        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            onClick={() => onOpenChange(false)}
            disabled={pending}
          >
            Cancelar
          </Button>
          <Button
            type="button"
            disabled={pending || !diasValidos || !fechaValida}
            onClick={() => onConfirm(fechaLimite)}
          >
            {pending ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Avanzando…
              </>
            ) : (
              confirmLabel
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
