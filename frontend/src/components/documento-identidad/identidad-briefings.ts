import type { LadoCapturaCamara } from './CapturaCamaraDocumento';
import {
  CAPTURA_CONSEJO,
  CAPTURA_LADO_AYUDA,
  CAPTURA_LADO_TITULO,
} from './captura-lado-textos';

export function identidadBriefingTipo() {
  return {
    title: 'Elegir documento',
    description:
      'Indique si va a identificarse con DNI, NIE o pasaporte. A continuación le pediremos las fotos necesarias.',
    ctaLabel: 'Elegir documento',
  };
}

export function identidadBriefingCaptura(lado: LadoCapturaCamara) {
  return {
    title: CAPTURA_LADO_TITULO[lado],
    description: `${CAPTURA_LADO_AYUDA[lado]} ${CAPTURA_CONSEJO}`,
    ctaLabel: lado === 'pasaporte' ? 'Preparar pasaporte' : 'Preparar captura',
  };
}

export function identidadBriefingRevision(opts?: { correccion?: boolean }) {
  if (opts?.correccion) {
    return {
      title: 'Corrija sus datos',
      description:
        'Su abogado le ha pedido revisar algunos campos. Corrija lo marcado y complete lo que falte antes de enviar de nuevo.',
      ctaLabel: 'Revisar y corregir',
    };
  }

  return {
    title: 'Resumen de sus datos',
    description:
      'Revise nombre, documento y resto de datos extraídos de las fotos. Corrija lo necesario antes de continuar.',
    ctaLabel: 'Revisar datos',
  };
}

export function identidadBriefingStepKey(
  paso: 'tipo' | 'captura' | 'revision',
  opts?: {
    lado?: LadoCapturaCamara;
    tipoEscaneo?: 'dni_nie' | 'pasaporte' | null;
    /** Nueva solicitud del abogado: no reutilizar el briefing del primer envío. */
    correccion?: boolean;
  },
): string {
  const sufijo = opts?.correccion ? '-correccion' : '';
  if (paso === 'tipo') return `identidad-tipo${sufijo}`;
  if (paso === 'revision') return `identidad-revision${sufijo}`;
  if (opts?.tipoEscaneo === 'pasaporte') return `identidad-captura-pasaporte${sufijo}`;
  if (opts?.lado === 'reverso') return `identidad-captura-reverso${sufijo}`;
  return `identidad-captura-anverso${sufijo}`;
}
