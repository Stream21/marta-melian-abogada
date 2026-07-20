import type { BloqueTextStyle } from '@/lib/escrito-format';
import {
  HOJA_ENCARGO_CIERRE,
  HOJA_ENCARGO_ESTIPULACIONES,
  HOJA_ENCARGO_EXPONEN,
  HOJA_ENCARGO_PROTECCION_DATOS_FILAS,
  HOJA_ENCARGO_REUNIDOS,
} from '@/lib/hoja-encargo-default-content';

export type BloqueEscritoType = 'title' | 'text' | 'section' | 'table' | 'columns';

export type BloqueColumnChildType =
  | 'title'
  | 'text'
  | 'section'
  | 'signature_client'
  | 'signature_lawyer';

/** Tipos añadibles desde la barra superior del documento */
export type RootBlockAddType = BloqueEscritoType | 'columns_1' | 'columns_2';

/** @deprecated Use BloqueEscritoType */
export type BloqueHojaEncargoType = BloqueEscritoType;

export interface BloqueTitle {
  id: string;
  type: 'title';
  title: string;
  showReferencia: boolean;
  style?: BloqueTextStyle;
}

export interface BloqueText {
  id: string;
  type: 'text';
  content: string;
  style?: BloqueTextStyle;
}

export interface BloqueSection {
  id: string;
  type: 'section';
  title: string;
  content: string;
  style?: BloqueTextStyle;
}

export interface BloqueTableRow {
  label: string;
  value: string;
}

export interface BloqueTable {
  id: string;
  type: 'table';
  clauseTitle: string;
  title: string;
  subtitle: string;
  rows: BloqueTableRow[];
}

export interface BloqueSignatureClient {
  id: string;
  type: 'signature_client';
  label?: string;
}

export interface BloqueSignatureLawyer {
  id: string;
  type: 'signature_lawyer';
  label?: string;
}

export type BloqueColumnChild =
  | BloqueTitle
  | BloqueText
  | BloqueSection
  | BloqueSignatureClient
  | BloqueSignatureLawyer;

export interface BloqueColumns {
  id: string;
  type: 'columns';
  columnCount: 1 | 2;
  children: (BloqueColumnChild | null)[];
}

export type BloqueEscrito =
  | BloqueTitle
  | BloqueText
  | BloqueSection
  | BloqueTable
  | BloqueColumns
  | BloqueSignatureClient
  | BloqueSignatureLawyer;

/** @deprecated Use BloqueEscrito */
export type BloqueHojaEncargo = BloqueEscrito;

export type TipoEscrito = 'hoja_encargo' | 'designacion' | 'rgpd';

export const TIPOS_ESCRITO: Array<{ value: TipoEscrito; label: string }> = [
  { value: 'hoja_encargo', label: 'Hoja de encargo' },
  { value: 'designacion', label: 'Designación' },
  { value: 'rgpd', label: 'RGPD' },
];

export interface VariablePreviewContext {
  despacho?: {
    nombreFirma?: string;
    nombreLetrada?: string;
    numColegiado?: string;
    direccion?: string;
    ciudad?: string;
    subtituloProfesional?: string;
    telefono?: string;
    email?: string;
    web?: string;
    nif?: string;
    colegioAbogados?: string;
    iban?: string;
    entidadBancaria?: string;
    titularCuenta?: string;
  };
  tramite?: {
    honorarios?: number;
    nombre?: string;
  };
  cliente?: {
    nombre?: string;
    nacionalidad?: string;
    tipoDocumento?: string;
    numDocumento?: string;
    fechaNacimiento?: string;
    lugarNacimiento?: string;
    domicilio?: string;
    codigoPostal?: string;
    ciudad?: string;
    telefono?: string;
    email?: string;
  };
}

import { eurosEnLetras } from './euros-en-letras';

const VARIABLE_REGEX = /\[\[([A-Z_]+)\]\]/g;

export function createBlockId(): string {
  return crypto.randomUUID();
}

export function insertVariableAtCursor(
  content: string,
  variableKey: string,
  selectionStart: number,
  selectionEnd: number,
): { content: string; cursor: number } {
  const token = `[[${variableKey}]]`;
  const next = content.slice(0, selectionStart) + token + content.slice(selectionEnd);
  const cursor = selectionStart + token.length;
  return { content: next, cursor };
}

export function buildPreviewValues(context: VariablePreviewContext): Record<string, string> {
  const honorarios = context.tramite?.honorarios ?? 450;
  const honorariosFormatted = new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'EUR',
  }).format(honorarios);

  const domicilioCliente = [
    context.cliente?.domicilio,
    context.cliente?.codigoPostal,
    context.cliente?.ciudad,
  ]
    .filter(Boolean)
    .join(', ');

  return {
    FECHA_ACTUAL: new Intl.DateTimeFormat('es-ES', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(new Date()),
    REFERENCIA_EXPEDIENTE: 'HE-2024-0892-MMG',
    NOMBRE_FIRMA: context.despacho?.nombreFirma ?? 'Marta Melián Guerra',
    NOMBRE_LETRADA: context.despacho?.nombreLetrada ?? 'D.ª MARTA MELIAN GUERRA',
    NUM_COLEGIADO: context.despacho?.numColegiado ?? '7.111',
    NIF_LETRADA: context.despacho?.nif ?? '44737558-M',
    COLEGIO_ABOGADOS: context.despacho?.colegioAbogados ?? 'Ilustre Colegio de Abogados de Las Palmas',
    SUBTITULO_PROFESIONAL: context.despacho?.subtituloProfesional ?? 'Abogada y Mediadora',
    DOMICILIO_DESPACHO: context.despacho?.direccion ?? 'C. Picachos, 43, local 2, 35200 Telde',
    CIUDAD_DESPACHO: context.despacho?.ciudad ?? 'Las Palmas de Gran Canaria',
    TELEFONO_DESPACHO: context.despacho?.telefono ?? '+34 652 292 450',
    EMAIL_DESPACHO: context.despacho?.email ?? 'mmguerra.abogada@gmail.com',
    WEB_DESPACHO: context.despacho?.web ?? 'https://martamelianguerraabogados.com/',
    IBAN: context.despacho?.iban ?? 'ES46 3076 0770 1329 5326 4823',
    ENTIDAD_BANCARIA: context.despacho?.entidadBancaria ?? 'CAJASIETE CAJA RURAL',
    TITULAR_CUENTA: context.despacho?.titularCuenta ?? 'MARTA MELIAN GUERRA',
    NOMBRE_CLIENTE: context.cliente?.nombre ?? 'Andreia Aelenei',
    DNI_CLIENTE: context.cliente?.numDocumento ?? '063298218',
    NACIONALIDAD_CLIENTE: context.cliente?.nacionalidad ?? 'RUMANA',
    TIPO_DOCUMENTO_CLIENTE: context.cliente?.tipoDocumento ?? 'PASAPORTE',
    NUM_DOCUMENTO_CLIENTE: context.cliente?.numDocumento ?? '063298218',
    FECHA_NACIMIENTO_CLIENTE: context.cliente?.fechaNacimiento ?? '29.08.1987',
    LUGAR_NACIMIENTO_CLIENTE: context.cliente?.lugarNacimiento ?? 'Baia Sprie, Rumanía',
    DOMICILIO_CLIENTE: domicilioCliente || 'Calle Doctor Agustín Millares Carlo 5, 51, 35100',
    CP_CLIENTE: context.cliente?.codigoPostal ?? '35100',
    TELEFONO_CLIENTE: context.cliente?.telefono ?? '+34 658 59 73 32',
    EMAIL_CLIENTE: context.cliente?.email ?? '',
    CUANTIA_TOTAL: honorariosFormatted,
    HONORARIOS_TRAMITE: honorariosFormatted,
    HONORARIOS_NUMERO: honorarios.toFixed(2).replace('.', ',') + ' €',
    HONORARIOS_LETRA: eurosEnLetras(honorarios),
    FORMA_PAGO: 'Según calendario de pagos acordado',
    FORMA_PAGO_DETALLE: 'Según calendario de pagos acordado',
    PAGOS_PROGRAMADOS: '- Primer pago: 200€\n- Segundo pago: 250€',
    DESCRIPCION_ASUNTO: context.tramite?.nombre ?? 'Nacionalidad española por residencia',
    NOMBRE_TRAMITE: context.tramite?.nombre ?? 'Nacionalidad española por residencia',
  };
}

export function substituteVariables(text: string, values: Record<string, string>): string {
  return text.replace(VARIABLE_REGEX, (_match, key: string) => values[key] ?? `[[${key}]]`);
}

export function splitTextWithVariables(text: string): Array<{ type: 'text' | 'variable'; value: string }> {
  const parts: Array<{ type: 'text' | 'variable'; value: string }> = [];
  let lastIndex = 0;
  let match: RegExpExecArray | null;

  const regex = new RegExp(VARIABLE_REGEX.source, 'g');
  while ((match = regex.exec(text)) !== null) {
    if (match.index > lastIndex) {
      parts.push({ type: 'text', value: text.slice(lastIndex, match.index) });
    }
    parts.push({ type: 'variable', value: match[1] });
    lastIndex = regex.lastIndex;
  }

  if (lastIndex < text.length) {
    parts.push({ type: 'text', value: text.slice(lastIndex) });
  }

  return parts;
}

/** Normaliza bloques legacy (header/footer) al cargar plantillas antiguas. */
export function normalizeLegacyBlocks(bloques: BloqueEscrito[]): BloqueEscrito[] {
  const normalized: BloqueEscrito[] = [];
  let hasTitle = false;

  for (const bloque of bloques) {
    const type = (bloque as { type?: string }).type;
    if (type === 'footer') continue;
    if (type === 'header') {
      normalized.push({
        id: bloque.id,
        type: 'title',
        title: (bloque as { title?: string }).title ?? 'HOJA DE ENCARGO',
        showReferencia: (bloque as { showReferencia?: boolean }).showReferencia ?? true,
      });
      hasTitle = true;
      continue;
    }
    if (type === 'title') hasTitle = true;
    normalized.push(bloque);
  }

  if (!hasTitle && normalized.length > 0) {
    normalized.unshift({
      id: createBlockId(),
      type: 'title',
      title: 'HOJA DE ENCARGO PROFESIONAL',
      showReferencia: true,
    });
  }

  return migrateSignatureBlocksToColumns(normalized).map((bloque) =>
    bloque.type === 'columns' ? normalizeColumnsBlock(bloque) : bloque,
  );
}

export function normalizeColumnsBlock(bloque: BloqueColumns): BloqueColumns {
  const columnCount: 1 | 2 = bloque.columnCount === 1 ? 1 : 2;
  const children: (BloqueColumnChild | null)[] = Array.from({ length: columnCount }, (_, index) => {
    const child = bloque.children[index];
    return child ?? null;
  });

  return { ...bloque, columnCount, children };
}

function isSignatureBlock(
  bloque: BloqueEscrito,
): bloque is BloqueSignatureClient | BloqueSignatureLawyer {
  return bloque.type === 'signature_client' || bloque.type === 'signature_lawyer';
}

function isAdjacentSignaturePair(
  a: BloqueEscrito,
  b: BloqueEscrito | undefined,
): b is BloqueSignatureClient | BloqueSignatureLawyer {
  if (!b) return false;
  return (
    (a.type === 'signature_lawyer' && b.type === 'signature_client') ||
    (a.type === 'signature_client' && b.type === 'signature_lawyer')
  );
}

export function migrateSignatureBlocksToColumns(bloques: BloqueEscrito[]): BloqueEscrito[] {
  const migrated: BloqueEscrito[] = [];
  let index = 0;

  while (index < bloques.length) {
    const current = bloques[index];
    const next = bloques[index + 1];

    if (current.type === 'columns') {
      migrated.push(current);
      index += 1;
      continue;
    }

    if (isSignatureBlock(current)) {
      if (isAdjacentSignaturePair(current, next)) {
        migrated.push({
          id: createBlockId(),
          type: 'columns',
          columnCount: 2,
          children: [current, next!],
        });
        index += 2;
        continue;
      }

      migrated.push({
        id: createBlockId(),
        type: 'columns',
        columnCount: 1,
        children: [current],
      });
      index += 1;
      continue;
    }

    migrated.push(current);
    index += 1;
  }

  return migrated;
}

export function createColumnChild(type: BloqueColumnChildType): BloqueColumnChild {
  switch (type) {
    case 'title':
      return { id: createBlockId(), type: 'title', title: '', showReferencia: false };
    case 'text':
      return { id: createBlockId(), type: 'text', content: '' };
    case 'section':
      return { id: createBlockId(), type: 'section', title: '', content: '' };
    case 'signature_client':
    case 'signature_lawyer':
      return { id: createBlockId(), type };
  }
}

/** @deprecated Use createColumnChild */
export function createSignatureBlock(
  type: 'signature_client' | 'signature_lawyer',
): BloqueSignatureClient | BloqueSignatureLawyer {
  return createColumnChild(type) as BloqueSignatureClient | BloqueSignatureLawyer;
}

export function createColumnsBlock(columnCount: 1 | 2 = 2): BloqueColumns {
  return {
    id: createBlockId(),
    type: 'columns',
    columnCount,
    children: Array.from({ length: columnCount }, () => null),
  };
}

export function createDefaultBlocks(tipo: TipoEscrito = 'hoja_encargo'): BloqueEscrito[] {
  switch (tipo) {
    case 'designacion':
      return [
        {
          id: createBlockId(),
          type: 'title',
          title: 'DOCUMENTO DE DESIGNACIÓN DE REPRESENTANTE',
          showReferencia: false,
        },
        { id: createBlockId(), type: 'text', content: 'RD 240/2007 y RD 1155/2024' },
        {
          id: createBlockId(),
          type: 'text',
          content:
            'Nombre [[NOMBRE_CLIENTE]], Nacionalidad [[NACIONALIDAD_CLIENTE]]; identificado con [[TIPO_DOCUMENTO_CLIENTE]] No. ' +
            '[[NUM_DOCUMENTO_CLIENTE]]; con fecha de nacimiento [[FECHA_NACIMIENTO_CLIENTE]] en [[LUGAR_NACIMIENTO_CLIENTE]]; ' +
            'domiciliado en [[DOMICILIO_CLIENTE]]; código postal [[CP_CLIENTE]]; y número móvil de contacto [[TELEFONO_CLIENTE]].',
        },
        {
          id: createBlockId(),
          type: 'text',
          content:
            'A los efectos de los artículos 5 y 66 de la Ley 39/2015, de 1 de octubre, del Procedimiento Administrativo Común de las ' +
            'Administraciones Públicas, y de acuerdo con lo establecido en el art. 197 del Real Decreto 1155/2024, de 11 de enero; ' +
            'y en los artículos 11 y 12 del Real Decreto 240/2007, de 16 de febrero, DESIGNO a la Abogada cuyos datos constan a ' +
            'continuación como representante para que formule en mi nombre los trámites correspondientes para obtener ' +
            '[[NOMBRE_TRAMITE]], presente y firme cuantos documentos sean reglamentariamente exigibles, así como a intervenir en ' +
            'cuantos trámites y diligencias requiera el procedimiento, salvo aquéllas en que sea necesaria mi comparecencia personal.',
        },
        {
          id: createBlockId(),
          type: 'text',
          content:
            '[[NOMBRE_LETRADA]], Abogada con nº de colegiada [[NUM_COLEGIADO]] del [[COLEGIO_ABOGADOS]], con DNI [[NIF_LETRADA]], ' +
            'domicilio en [[DOMICILIO_DESPACHO]], tlf. [[TELEFONO_DESPACHO]] y email [[EMAIL_DESPACHO]]',
        },
        { id: createBlockId(), type: 'text', content: 'En [[CIUDAD_DESPACHO]] a [[FECHA_ACTUAL]]' },
        {
          id: createBlockId(),
          type: 'columns',
          columnCount: 2,
          children: [createSignatureBlock('signature_client'), createSignatureBlock('signature_lawyer')],
        },
      ];
    case 'rgpd':
      return [
        {
          id: createBlockId(),
          type: 'title',
          title: 'CONTRATO DE ENCARGO DE TRATAMIENTO DE DATOS PERSONALES',
          showReferencia: false,
        },
        {
          id: createBlockId(),
          type: 'section',
          title: 'Objeto del encargo del tratamiento',
          content:
            'Teniendo en cuenta que las partes mantienen un acuerdo de prestación de servicios que implica el acceso a datos de carácter personal ' +
            'responsabilidad del cliente por parte del proveedor, mediante este contrato se da cumplimiento a la exigencia del artículo 28.3 del ' +
            'Reglamento General de Protección de Datos (UE 2016/679).\n\n' +
            'Mediante las presentes cláusulas se habilita a [[NOMBRE_LETRADA]], NIF nº [[NIF_LETRADA]], en adelante encargado de tratamiento, ' +
            'para tratar por cuenta de [[NOMBRE_CLIENTE]], [[TIPO_DOCUMENTO_CLIENTE]] nº [[NUM_DOCUMENTO_CLIENTE]], en adelante Responsable del ' +
            'Tratamiento, los datos de carácter personal necesarios para la prestación de [[NOMBRE_TRAMITE]].',
        },
        {
          id: createBlockId(),
          type: 'section',
          title: 'Identificación de la información afectada',
          content:
            'Para la ejecución de las prestaciones derivadas del cumplimiento del objeto de este encargo, el Responsable del tratamiento pone a ' +
            'disposición del Encargado del Tratamiento la información que se describe a continuación:\n\n' +
            '* Datos identificativos de trabajadores, clientes, proveedores y solicitantes',
        },
        {
          id: createBlockId(),
          type: 'section',
          title: 'Duración',
          content:
            'La duración del presente acuerdo será la misma que la del acuerdo de prestación de servicios que lo origina.',
        },
        {
          id: createBlockId(),
          type: 'section',
          title: 'Obligaciones del encargado del tratamiento',
          content:
            'El encargado del tratamiento y todo su personal se obliga a utilizar los datos sólo para la finalidad de este encargo, tratarlos ' +
            'según las instrucciones del responsable, llevar registro de actividades cuando proceda, no subcontratar sin autorización, mantener ' +
            'secreto, garantizar confidencialidad del personal autorizado, no ceder datos sin autorización, garantizar seguridad de los sistemas, ' +
            'comunicar al responsable el ejercicio de derechos por los afectados, verificar medidas de seguridad, seudonimizar y cifrar cuando ' +
            'proceda, notificar brechas en 72 horas, apoyar consultas previas, facilitar información para auditorías, implantar medidas de ' +
            'seguridad y suprimir o devolver los datos al finalizar el contrato.',
        },
        {
          id: createBlockId(),
          type: 'section',
          title: 'Obligaciones del responsable del tratamiento',
          content:
            'Entregar los datos necesarios, realizar consultas previas, velar por el cumplimiento normativo y supervisar el tratamiento, ' +
            'incluidas inspecciones y auditorías.',
        },
        {
          id: createBlockId(),
          type: 'section',
          title: 'Responsabilidades',
          content:
            'El Responsable del Tratamiento queda exonerado de responsabilidad derivada del incumplimiento del Encargado, respondiendo éste ' +
            'personalmente ante las Autoridades de Protección de Datos y ante reclamaciones civiles y penales.',
        },
        {
          id: createBlockId(),
          type: 'section',
          title: 'Declaración responsable de cumplimiento del Reglamento 2016/679',
          content:
            'El Encargado de Tratamiento declara disponer de registro de actividades, cumplir el RGPD, haber realizado análisis de riesgos, ' +
            'haber adoptado medidas de seguridad técnicas y organizativas, mantener deber de secreto y realizar controles periódicos de cumplimiento.',
        },
        {
          id: createBlockId(),
          type: 'section',
          title: 'Información protección de datos',
          content:
            'Ambas partes reconocen haber sido informadas de las finalidades del tratamiento, de la posibilidad de información comercial por ' +
            'interés legítimo y de cómo ejercer sus derechos. El encargado declara cumplir los compromisos de la cláusula 7.',
        },
        { id: createBlockId(), type: 'text', content: 'En [[CIUDAD_DESPACHO]], a [[FECHA_ACTUAL]]' },
        {
          id: createBlockId(),
          type: 'columns',
          columnCount: 2,
          children: [createSignatureBlock('signature_client'), createSignatureBlock('signature_lawyer')],
        },
      ];
    case 'hoja_encargo':
    default:
      return [
        {
          id: createBlockId(),
          type: 'title',
          title: 'HOJA DE ENCARGO PROFESIONAL',
          showReferencia: true,
          style: { align: 'center', fontSize: 16 },
        },
        {
          id: createBlockId(),
          type: 'text',
          content: 'En [[CIUDAD_DESPACHO]], a [[FECHA_ACTUAL]]',
          style: { align: 'center', fontSize: 11 },
        },
        { id: createBlockId(), type: 'section', title: 'REUNIDOS', content: HOJA_ENCARGO_REUNIDOS },
        { id: createBlockId(), type: 'section', title: 'EXPONEN', content: HOJA_ENCARGO_EXPONEN },
        { id: createBlockId(), type: 'section', title: 'ESTIPULACIONES', content: HOJA_ENCARGO_ESTIPULACIONES },
        {
          id: createBlockId(),
          type: 'table',
          clauseTitle: 'DÉCIMA.- PROTECCIÓN DE DATOS PERSONALES',
          title: 'INFORMACIÓN BÁSICA',
          subtitle: 'Información básica sobre Protección de Datos',
          rows: HOJA_ENCARGO_PROTECCION_DATOS_FILAS,
        },
        { id: createBlockId(), type: 'text', content: HOJA_ENCARGO_CIERRE },
        {
          id: createBlockId(),
          type: 'columns',
          columnCount: 2,
          children: [createSignatureBlock('signature_lawyer'), createSignatureBlock('signature_client')],
        },
      ];
  }
}

export function createEmptyBlock(type: BloqueEscritoType): BloqueEscrito {
  switch (type) {
    case 'title':
      return { id: createBlockId(), type: 'title', title: 'TÍTULO DEL DOCUMENTO', showReferencia: true };
    case 'text':
      return { id: createBlockId(), type: 'text', content: '' };
    case 'section':
      return { id: createBlockId(), type: 'section', title: 'Nueva sección', content: '' };
    case 'table':
      return {
        id: createBlockId(),
        type: 'table',
        clauseTitle: '',
        title: '',
        subtitle: '',
        rows: [{ label: '', value: '' }],
      };
    case 'columns':
      return createColumnsBlock(2);
  }
}
