import type { AccesoExpedienteResponse } from '@/api/client';
import type { ContratacionView } from './types';

export interface JourneyStepBriefing {
  key: string;
  title: string;
  description: string;
  ctaLabel: string;
}

function briefingFirma(tipo: string, label: string): JourneyStepBriefing {
  switch (tipo) {
    case 'hoja_encargo':
      return {
        key: `firma-${tipo}`,
        title: label,
        description:
          'Es el contrato con el despacho: servicio, honorarios y condiciones. ' +
          'Tendrás que leerlo hasta el final, aceptarlo y firmar en la pantalla. ' +
          'Sin esta firma no se puede seguir.',
        ctaLabel: 'Abrir la hoja de encargo',
      };
    case 'designacion':
      return {
        key: `firma-${tipo}`,
        title: label,
        description:
          'Con esto autorizas a la abogada a representarte ante la Administración. ' +
          'Léelo hasta el final, acéptalo y firma para poder tramitar tu expediente.',
        ctaLabel: 'Abrir documento',
      };
    case 'rgpd':
      return {
        key: `firma-${tipo}`,
        title: label,
        description:
          'Información sobre el uso de tus datos personales. ' +
          'Léelo completo, acéptalo y firma para que el despacho pueda gestionar tu expediente.',
        ctaLabel: 'Abrir documento',
      };
    default:
      return {
        key: `firma-${tipo}`,
        title: label,
        description:
          'Lee el documento hasta el final. Después podrás aceptarlo y firmarlo en la pantalla.',
        ctaLabel: 'Abrir documento',
      };
  }
}

export function briefingForView(
  view: ContratacionView,
  data: AccesoExpedienteResponse,
): JourneyStepBriefing | null {
  switch (view.type) {
    case 'doc': {
      const doc = (data.documentosRequeridos ?? []).find((d) => d.id === view.docId);
      if (!doc) return null;
      return {
        key: `doc-${view.docId}`,
        title: doc.nombre,
        description:
          doc.descripcion?.trim() ||
          'En el siguiente paso: 1) elija la foto o el PDF, 2) pulse Enviar para entregarlo a su abogado.',
        ctaLabel: 'Continuar',
      };
    }
    case 'otp':
      return {
        key: 'otp',
        title: 'Confirma tu móvil',
        description: data.firmas?.telefonoMascara
          ? `Antes de firmar, te enviaremos un SMS al ${data.firmas.telefonoMascara} con un código de 6 dígitos. ` +
            'Lo introducirás en la siguiente pantalla.'
          : 'Antes de firmar, te enviaremos un SMS con un código de 6 dígitos para confirmar tu móvil.',
        ctaLabel: 'Continuar',
      };
    case 'firma': {
      const doc = (data.documentosFirma ?? []).find((d) => d.tipo === view.tipo);
      return briefingFirma(view.tipo, doc?.label ?? 'Firmar documento');
    }
    case 'pago':
      return {
        key: 'pago',
        title: 'Pago inicial',
        description: data.resumenPago?.metodoPagoLabel
          ? `Ahora verás cómo hacer el pago (${data.resumenPago.metodoPagoLabel}). ` +
            'Cuando el despacho lo confirme, podrás seguir con el expediente.'
          : 'Ahora verás las instrucciones para el pago acordado con el despacho.',
        ctaLabel: 'Ver el pago',
      };
    case 'resumen_final':
      return {
        key: 'resumen-final',
        title: 'Resumen',
        description:
          'Aquí verás lo que has completado. Tu abogado recibirá la información y te dirá los siguientes pasos.',
        ctaLabel: 'Ver resumen',
      };
    default:
      return null;
  }
}
