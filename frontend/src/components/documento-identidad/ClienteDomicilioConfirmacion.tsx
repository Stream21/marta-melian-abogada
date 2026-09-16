import { useState } from 'react';
import { Check, Loader2, MapPin, PencilLine } from 'lucide-react';
import type { ClienteInput } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface ClienteDomicilioConfirmacionProps {
  datosActuales: ClienteInput;
  onSinCambios: () => void;
  onActualizar: (datos: ClienteInput) => void;
  onVolver: () => void;
  guardando?: boolean;
}

function resumenDomicilio(datos: ClienteInput): string {
  const partes = [
    datos.domicilio?.trim(),
    [datos.codigoPostal?.trim(), datos.ciudad?.trim()].filter(Boolean).join(' '),
    datos.provincia?.trim(),
  ].filter(Boolean);
  return partes.length > 0 ? partes.join(', ') : 'Sin domicilio registrado';
}

export function ClienteDomicilioConfirmacion({
  datosActuales,
  onSinCambios,
  onActualizar,
  onVolver,
  guardando,
}: ClienteDomicilioConfirmacionProps) {
  const [modo, setModo] = useState<'pregunta' | 'formulario'>('pregunta');
  const [domicilio, setDomicilio] = useState(datosActuales.domicilio ?? '');
  const [codigoPostal, setCodigoPostal] = useState(datosActuales.codigoPostal ?? '');
  const [ciudad, setCiudad] = useState(datosActuales.ciudad ?? '');
  const [provincia, setProvincia] = useState(datosActuales.provincia ?? '');
  const [error, setError] = useState<string | null>(null);

  const handleGuardar = () => {
    const domicilioTrim = domicilio.trim();
    const ciudadTrim = ciudad.trim();
    if ('' === domicilioTrim || '' === ciudadTrim) {
      setError('Indique al menos el domicilio y la ciudad.');
      return;
    }
    setError(null);
    onActualizar({
      ...datosActuales,
      domicilio: domicilioTrim,
      codigoPostal: codigoPostal.trim(),
      ciudad: ciudadTrim,
      provincia: provincia.trim(),
    });
  };

  if (modo === 'formulario') {
    return (
      <div className="panel space-y-5 p-6">
        <div>
          <h3 className="text-base font-semibold">Actualice su domicilio</h3>
          <p className="mt-1 text-sm text-muted-foreground">
            Indique la dirección actual. El resto de sus datos se mantienen.
          </p>
        </div>

        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-2 sm:col-span-2">
            <Label htmlFor="domicilio-nuevo">Domicilio</Label>
            <Input
              id="domicilio-nuevo"
              className="input-field"
              value={domicilio}
              onChange={(e) => setDomicilio(e.target.value)}
              disabled={guardando}
              autoComplete="street-address"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="cp-nuevo">Código postal</Label>
            <Input
              id="cp-nuevo"
              className="input-field"
              value={codigoPostal}
              onChange={(e) => setCodigoPostal(e.target.value)}
              disabled={guardando}
              autoComplete="postal-code"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="ciudad-nueva">Ciudad / municipio</Label>
            <Input
              id="ciudad-nueva"
              className="input-field"
              value={ciudad}
              onChange={(e) => setCiudad(e.target.value)}
              disabled={guardando}
              autoComplete="address-level2"
            />
          </div>
          <div className="space-y-2 sm:col-span-2">
            <Label htmlFor="provincia-nueva">Provincia</Label>
            <Input
              id="provincia-nueva"
              className="input-field"
              value={provincia}
              onChange={(e) => setProvincia(e.target.value)}
              disabled={guardando}
              autoComplete="address-level1"
            />
          </div>
        </div>

        {error && <p className="text-sm text-destructive">{error}</p>}

        <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
          <Button
            type="button"
            variant="ghost"
            onClick={() => {
              setError(null);
              setModo('pregunta');
            }}
            disabled={guardando}
          >
            Volver
          </Button>
          <Button type="button" onClick={handleGuardar} disabled={guardando}>
            {guardando ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Guardando…
              </>
            ) : (
              'Guardar y continuar'
            )}
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="panel space-y-5 p-6">
      <div>
        <h3 className="text-base font-semibold">¿Ha cambiado de domicilio?</h3>
        <p className="mt-1 text-sm text-muted-foreground">
          Como ya tenemos sus datos de un trámite anterior, solo necesitamos confirmar su dirección
          antes de continuar con la firma y el pago.
        </p>
      </div>

      <div className="flex gap-3 rounded-lg border border-border bg-muted/30 p-4">
        <MapPin className="mt-0.5 h-5 w-5 shrink-0 text-primary" aria-hidden />
        <div className="min-w-0">
          <p className="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
            Domicilio registrado
          </p>
          <p className="mt-1 text-sm text-foreground">{resumenDomicilio(datosActuales)}</p>
        </div>
      </div>

      <div className="grid gap-3 sm:grid-cols-2">
        <Button
          type="button"
          size="lg"
          className="h-auto flex-col items-start gap-2 whitespace-normal px-4 py-4 text-left"
          onClick={onSinCambios}
          disabled={guardando}
        >
          <span className="flex items-center gap-2 font-semibold">
            {guardando ? (
              <Loader2 className="h-5 w-5 shrink-0 animate-spin" />
            ) : (
              <Check className="h-5 w-5 shrink-0" />
            )}
            No, es el mismo
          </span>
          <span className="text-xs font-normal opacity-90">
            Continuar a la firma de documentos y al pago.
          </span>
        </Button>

        <Button
          type="button"
          variant="outline"
          size="lg"
          className="h-auto flex-col items-start gap-2 whitespace-normal px-4 py-4 text-left"
          onClick={() => setModo('formulario')}
          disabled={guardando}
        >
          <span className="flex items-center gap-2 font-semibold">
            <PencilLine className="h-5 w-5 shrink-0" />
            Sí, ha cambiado
          </span>
          <span className="text-xs font-normal text-muted-foreground">
            Actualice domicilio, código postal, ciudad y provincia.
          </span>
        </Button>
      </div>

      <Button type="button" variant="ghost" className="w-full sm:w-auto" onClick={onVolver} disabled={guardando}>
        Volver a la elección del documento
      </Button>
    </div>
  );
}
