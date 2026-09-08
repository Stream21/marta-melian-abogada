import { CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface ContratacionHubProps {
  pasoLabel: string;
  notaDevolucion?: string | null;
  puedeConfirmar: boolean;
  completando?: boolean;
  onConfirmar?: () => void;
}

export function ContratacionHub({
  pasoLabel,
  notaDevolucion,
  puedeConfirmar,
  completando,
  onConfirmar,
}: ContratacionHubProps) {
  return (
    <div className="mx-auto flex max-w-md flex-col items-center py-6 text-center">
      <div className="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
        <CheckCircle2 className="h-6 w-6" />
      </div>
      <p className="mt-4 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
        {pasoLabel}
      </p>
      <h2 className="mt-1 text-xl font-semibold text-foreground">Todo listo</h2>
      <p className="mt-2 text-sm text-muted-foreground">
        Ha completado esta parte del proceso.
      </p>

      {notaDevolucion?.trim() && (
        <div className="mt-4 w-full rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-left text-sm text-amber-950">
          <p className="font-semibold">Mensaje de su abogado</p>
          <p className="mt-1 whitespace-pre-wrap text-amber-900/95">{notaDevolucion}</p>
        </div>
      )}

      {onConfirmar ? (
        <Button
          className="mt-5 min-h-[44px] w-full"
          size="lg"
          onClick={onConfirmar}
          disabled={!puedeConfirmar || completando}
        >
          {completando ? 'Enviando…' : 'Continuar'}
        </Button>
      ) : (
        <p className="mt-4 text-sm text-muted-foreground">
          Su abogado revisará la información y le avisará para continuar.
        </p>
      )}
    </div>
  );
}
