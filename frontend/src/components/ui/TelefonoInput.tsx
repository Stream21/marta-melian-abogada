import { Input } from '@/components/ui/input';
import { cerrarTecladoAlEnter } from '@/lib/cerrar-teclado';
import { sanitizarTelefono } from '@/lib/telefono';

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
  return (
    <Input
      id={id}
      type="tel"
      inputMode="tel"
      autoComplete="tel"
      enterKeyHint="done"
      value={value}
      onChange={(e) => onChange(sanitizarTelefono(e.target.value))}
      onKeyDown={cerrarTecladoAlEnter}
      onBlur={onBlur}
      placeholder={placeholder}
      required={required}
      disabled={disabled}
      className={className}
    />
  );
}
