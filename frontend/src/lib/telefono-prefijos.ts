export type TelefonoPrefijo = {
  code: string;
  pais: string;
};

/** Prefijos habituales en extranjería y comunicación con clientes del bufete. */
export const TELEFONO_PREFIJOS: TelefonoPrefijo[] = [
  { code: '+34', pais: 'España' },
  { code: '+351', pais: 'Portugal' },
  { code: '+33', pais: 'Francia' },
  { code: '+39', pais: 'Italia' },
  { code: '+49', pais: 'Alemania' },
  { code: '+44', pais: 'Reino Unido' },
  { code: '+1', pais: 'EE.UU. / Canadá' },
  { code: '+212', pais: 'Marruecos' },
  { code: '+213', pais: 'Argelia' },
  { code: '+221', pais: 'Senegal' },
  { code: '+234', pais: 'Nigeria' },
  { code: '+57', pais: 'Colombia' },
  { code: '+58', pais: 'Venezuela' },
  { code: '+51', pais: 'Perú' },
  { code: '+593', pais: 'Ecuador' },
  { code: '+54', pais: 'Argentina' },
  { code: '+56', pais: 'Chile' },
  { code: '+591', pais: 'Bolivia' },
  { code: '+595', pais: 'Paraguay' },
  { code: '+598', pais: 'Uruguay' },
  { code: '+55', pais: 'Brasil' },
  { code: '+52', pais: 'México' },
  { code: '+503', pais: 'El Salvador' },
  { code: '+504', pais: 'Honduras' },
  { code: '+505', pais: 'Nicaragua' },
  { code: '+506', pais: 'Costa Rica' },
  { code: '+507', pais: 'Panamá' },
  { code: '+53', pais: 'Cuba' },
  { code: '+1809', pais: 'Rep. Dominicana' },
  { code: '+40', pais: 'Rumanía' },
  { code: '+380', pais: 'Ucrania' },
  { code: '+7', pais: 'Rusia / Kazajistán' },
  { code: '+86', pais: 'China' },
  { code: '+91', pais: 'India' },
  { code: '+92', pais: 'Pakistán' },
  { code: '+63', pais: 'Filipinas' },
  { code: '+90', pais: 'Turquía' },
];

export const DEFAULT_TELEFONO_PREFIJO = '+34';

export function splitTelefono(full: string): { prefijo: string; numero: string } {
  const trimmed = full.trim();
  if ('' === trimmed) {
    return { prefijo: DEFAULT_TELEFONO_PREFIJO, numero: '' };
  }

  const collapsed = trimmed.replace(/[\s\-().]/g, '');

  if (collapsed.startsWith('+')) {
    const sorted = [...TELEFONO_PREFIJOS].sort((a, b) => b.code.length - a.code.length);
    for (const { code } of sorted) {
      if (collapsed.startsWith(code)) {
        return { prefijo: code, numero: collapsed.slice(code.length) };
      }
    }

    const match = collapsed.match(/^(\+\d{1,4})(\d+)$/);
    if (match) {
      return { prefijo: match[1], numero: match[2] };
    }
  }

  const digits = collapsed.replace(/\D/g, '');

  if (digits.startsWith('00')) {
    return splitTelefono(`+${digits.slice(2)}`);
  }

  if (digits.startsWith('34') && 11 === digits.length && /^[67]\d{8}$/.test(digits.slice(2))) {
    return { prefijo: DEFAULT_TELEFONO_PREFIJO, numero: digits.slice(2) };
  }

  if (/^[67]\d{8}$/.test(digits)) {
    return { prefijo: DEFAULT_TELEFONO_PREFIJO, numero: digits };
  }

  if ('' !== digits) {
    return { prefijo: DEFAULT_TELEFONO_PREFIJO, numero: digits };
  }

  return { prefijo: DEFAULT_TELEFONO_PREFIJO, numero: '' };
}

export function joinTelefono(prefijo: string, numero: string): string {
  const local = numero.replace(/\D/g, '');
  if ('' === local) {
    return '';
  }

  return `${prefijo}${local}`;
}

export function prefijosParaSelector(prefijoActual: string): TelefonoPrefijo[] {
  if (TELEFONO_PREFIJOS.some((p) => p.code === prefijoActual)) {
    return TELEFONO_PREFIJOS;
  }

  return [{ code: prefijoActual, pais: 'Actual' }, ...TELEFONO_PREFIJOS];
}
