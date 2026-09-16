export type PlataformaTramitacionValue =
  | 'mercurio'
  | 'lexnet'
  | 'ministerio_justicia'
  | 'registro_civil'
  | 'notaria'
  | 'registro_propiedad';

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
  {
    value: 'ministerio_justicia',
    label: 'Ministerio de Justicia',
    description: 'Trámites ante el Ministerio de Justicia (sin flujo guiado aún).',
  },
  {
    value: 'registro_civil',
    label: 'Registro Civil',
    description: 'Gestiones ante el Registro Civil (sin flujo guiado aún).',
  },
  {
    value: 'notaria',
    label: 'Notaría',
    description: 'Actuaciones notariales (sin flujo guiado aún).',
  },
  {
    value: 'registro_propiedad',
    label: 'Registro de la Propiedad',
    description: 'Inscripciones y gestiones registrales (sin flujo guiado aún).',
  },
];

export function getPlataformaTramitacionOption(value: string) {
  return PLATAFORMAS_TRAMITACION.find((p) => p.value === value);
}
