import { useMemo } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import {
  joinTelefono,
  prefijosParaSelector,
  splitTelefono,
} from '@/lib/telefono-prefijos';

type TelefonoInputProps = {
  id?: string;
  value: string;
  onChange: (value: string) => void;
  required?: boolean;
  className?: string;
  placeholder?: string;
};

export function TelefonoInput({
  id,
  value,
  onChange,
  required = false,
  className,
  placeholder = '612 345 678',
}: TelefonoInputProps) {
  const { prefijo, numero } = useMemo(() => splitTelefono(value), [value]);
  const opciones = useMemo(() => prefijosParaSelector(prefijo), [prefijo]);

  return (
    <div className={cn('flex gap-2', className)}>
      <select
        id={id ? `${id}-prefijo` : undefined}
        className="input-field h-9 w-[10.5rem] shrink-0"
        value={prefijo}
        onChange={(e) => onChange(joinTelefono(e.target.value, numero))}
        aria-label="Prefijo internacional"
      >
        {opciones.map((p) => (
          <option key={p.code} value={p.code}>
            {p.code} {p.pais}
          </option>
        ))}
      </select>
      <Input
        id={id}
        type="tel"
        inputMode="tel"
        autoComplete="tel-national"
        value={numero}
        onChange={(e) => onChange(joinTelefono(prefijo, e.target.value))}
        placeholder={placeholder}
        required={required}
        className="min-w-0 flex-1"
      />
    </div>
  );
}
