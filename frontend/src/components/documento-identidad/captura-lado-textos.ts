import type { LadoCapturaCamara } from './CapturaCamaraDocumento';

export const CAPTURA_LADO_TITULO: Record<LadoCapturaCamara, string> = {
  anverso: 'Prepare la delantera',
  reverso: 'Prepare la trasera',
  pasaporte: 'Prepare el pasaporte',
};

export const CAPTURA_LADO_AYUDA: Record<LadoCapturaCamara, string> = {
  anverso: 'Coloque la tarjeta en horizontal, con la foto hacia arriba.',
  reverso: 'Gire la tarjeta. La banda negra inferior debe verse completa.',
  pasaporte: 'Abra la página interior con su foto y datos.',
};

export const CAPTURA_LADO_ETIQUETA: Record<LadoCapturaCamara, string> = {
  anverso: 'Delantera',
  reverso: 'Trasera',
  pasaporte: 'Pasaporte',
};

export const CAPTURA_LADO_DESCRIPCION: Record<LadoCapturaCamara, string> = {
  anverso: 'Cara con foto',
  reverso: 'Banda MRZ inferior',
  pasaporte: 'Página de datos',
};

export const CAPTURA_CONSEJO =
  'Buena luz, sin reflejos. El documento debe verse entero en el marco.';
