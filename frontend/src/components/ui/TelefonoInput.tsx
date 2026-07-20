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
  onBlur?: () => void;
  required?: boolean;
  disabled?: boolean;
  className?: string;
  placeholder?: string;
};

export function TelefonoInput({
  id,
  value,
  onChange,
  onBlur,
  required = false,
  disabled = false,
  className,
  placeholder = '612 345 678',
}: TelefonoInputProps) {
  const { prefijo, numero } = useMemo(() => splitTelefono(value), [value]);
  const opciones = useMemo(() => prefijosParaSelector(prefijo), [prefijo]);

  return (
    <div className={cn('flex gap-2', className)}>
      <select
        id={id ? `${id}-prefijo` : undefined}
        className="input-field h-9 w-[4.75rem] shrink-0 px-2 text-sm disabled:cursor-not-allowed disabled:opacity-60 sm:w-[6.5rem] sm:px-3"
        value={prefijo}
        onChange={(e) => onChange(joinTelefono(e.target.value, numero))}
        onBlur={onBlur}
        disabled={disabled}
        aria-label="Prefijo internacional"
      >
        {opciones.map((p) => (
          <option key={p.code} value={p.code} title={p.pais}>
            {p.code}
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
        onBlur={onBlur}
        placeholder={placeholder}
        required={required}
        disabled={disabled}
        className="min-w-0 flex-1"
      />
    </div>
  );
}
