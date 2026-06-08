export type PlataformaTramitacionValue = 'mercurio' | 'lexnet';

export const PLATAFORMAS_TRAMITACION: {
  value: PlataformaTramitacionValue;
  label: string;
  description: string;
}[] = [
  {
    value: 'mercurio',
    label: 'Mercurio',
    description: 'Tramitación administrativa ante oficinas de extranjería.',
  },
  {
    value: 'lexnet',
    label: 'LexNET',
    description: 'Vía judicial y recursos contencioso-administrativos.',
  },
];

export function getPlataformaTramitacionOption(value: string) {
  return PLATAFORMAS_TRAMITACION.find((p) => p.value === value);
}
