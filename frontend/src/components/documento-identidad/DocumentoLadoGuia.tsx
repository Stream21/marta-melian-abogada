import { cn } from '@/lib/utils';

type LadoGuia = 'anverso' | 'reverso' | 'pasaporte';

interface DocumentoLadoGuiaProps {
  lado: LadoGuia;
  className?: string;
}

export function DocumentoLadoGuia({ lado, className }: DocumentoLadoGuiaProps) {
  return (
    <div className={cn('rounded-lg border border-border bg-card p-4', className)}>
      <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
        <div className="mx-auto shrink-0 sm:mx-0">
          {lado === 'anverso' && <GuiaAnverso />}
          {lado === 'reverso' && <GuiaReverso />}
          {lado === 'pasaporte' && <GuiaPasaporte />}
        </div>
        <div className="min-w-0 flex-1 text-sm">
          {lado === 'anverso' && (
            <>
              <p className="font-semibold text-foreground">Anverso — cara con su foto</p>
              <p className="mt-1 text-muted-foreground">
                Es la parte delantera del DNI/NIE: aparece su fotografía, nombre, fecha de nacimiento y
                número de documento. Coloque el documento <strong>en horizontal</strong>, como en el dibujo.
              </p>
            </>
          )}
          {lado === 'reverso' && (
            <>
              <p className="font-semibold text-foreground">Reverso — cara con banda MRZ</p>
              <p className="mt-1 text-muted-foreground">
                Es la parte trasera: domicilio, padres y, abajo, <strong>tres líneas de caracteres</strong>{' '}
                (bandeja MRZ con símbolos <code className="text-xs">&lt;&lt;&lt;</code>). También en
                horizontal, con la banda MRZ abajo y legible.
              </p>
            </>
          )}
          {lado === 'pasaporte' && (
            <>
              <p className="font-semibold text-foreground">Página de identificación</p>
              <p className="mt-1 text-muted-foreground">
                Fotografíe la página interior con su foto y datos personales, en horizontal y sin recortes.
              </p>
            </>
          )}
        </div>
      </div>
    </div>
  );
}

function GuiaAnverso() {
  return (
    <svg viewBox="0 0 200 126" className="h-24 w-40 text-primary" aria-hidden>
      <rect x="4" y="4" width="192" height="118" rx="8" fill="currentColor" fillOpacity="0.08" stroke="currentColor" strokeWidth="2" />
      <rect x="14" y="18" width="44" height="54" rx="4" fill="currentColor" fillOpacity="0.2" stroke="currentColor" strokeWidth="1.5" />
      <circle cx="36" cy="38" r="10" fill="currentColor" fillOpacity="0.35" />
      <text x="70" y="28" fontSize="9" fill="currentColor" fontWeight="bold">ESPAÑA</text>
      <rect x="70" y="34" width="70" height="4" rx="1" fill="currentColor" fillOpacity="0.25" />
      <rect x="70" y="42" width="90" height="4" rx="1" fill="currentColor" fillOpacity="0.2" />
      <rect x="70" y="50" width="60" height="4" rx="1" fill="currentColor" fillOpacity="0.2" />
      <rect x="14" y="82" width="120" height="4" rx="1" fill="currentColor" fillOpacity="0.15" />
      <text x="100" y="112" fontSize="8" fill="currentColor" textAnchor="middle" fontWeight="600">ANVERSO</text>
    </svg>
  );
}

function GuiaReverso() {
  return (
    <svg viewBox="0 0 200 126" className="h-24 w-40 text-primary" aria-hidden>
      <rect x="4" y="4" width="192" height="118" rx="8" fill="currentColor" fillOpacity="0.08" stroke="currentColor" strokeWidth="2" />
      <rect x="14" y="16" width="100" height="4" rx="1" fill="currentColor" fillOpacity="0.2" />
      <rect x="14" y="26" width="80" height="4" rx="1" fill="currentColor" fillOpacity="0.15" />
      <rect x="14" y="36" width="90" height="4" rx="1" fill="currentColor" fillOpacity="0.15" />
      <rect x="14" y="52" width="70" height="28" rx="3" fill="currentColor" fillOpacity="0.1" stroke="currentColor" strokeWidth="1" strokeDasharray="3 2" />
      <rect x="14" y="88" width="172" height="10" rx="2" fill="currentColor" fillOpacity="0.35" stroke="currentColor" strokeWidth="1.5" />
      <rect x="14" y="100" width="172" height="10" rx="2" fill="currentColor" fillOpacity="0.35" stroke="currentColor" strokeWidth="1.5" />
      <text x="100" y="95" fontSize="7" fill="currentColor" textAnchor="middle" fontWeight="bold">MRZ &lt;&lt;&lt;</text>
      <text x="100" y="112" fontSize="8" fill="currentColor" textAnchor="middle" fontWeight="600">REVERSO</text>
    </svg>
  );
}

function GuiaPasaporte() {
  return (
    <svg viewBox="0 0 200 126" className="h-24 w-40 text-primary" aria-hidden>
      <rect x="4" y="4" width="192" height="118" rx="4" fill="currentColor" fillOpacity="0.08" stroke="currentColor" strokeWidth="2" />
      <rect x="14" y="14" width="50" height="62" rx="3" fill="currentColor" fillOpacity="0.2" stroke="currentColor" strokeWidth="1.5" />
      <rect x="72" y="18" width="110" height="5" rx="1" fill="currentColor" fillOpacity="0.25" />
      <rect x="72" y="28" width="90" height="4" rx="1" fill="currentColor" fillOpacity="0.2" />
      <rect x="72" y="38" width="100" height="4" rx="1" fill="currentColor" fillOpacity="0.2" />
      <rect x="14" y="88" width="168" height="8" rx="1" fill="currentColor" fillOpacity="0.3" />
      <text x="100" y="112" fontSize="8" fill="currentColor" textAnchor="middle" fontWeight="600">PÁGINA DATOS</text>
    </svg>
  );
}
