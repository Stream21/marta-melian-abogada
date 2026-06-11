import { buildPreviewValues, substituteVariables, type VariablePreviewContext } from '@/lib/hoja-encargo-variables';

export interface DespachoMembreteFields {
  nombreFirma: string;
  subtituloProfesional: string;
  direccion: string;
  web: string;
  email: string;
  telefono: string;
  colegioAbogados: string;
  nif: string;
}

export function buildDespachoMembreteValues(fields: DespachoMembreteFields): Record<string, string> {
  return buildPreviewValues({
    despacho: {
      nombreFirma: fields.nombreFirma,
      subtituloProfesional: fields.subtituloProfesional,
      direccion: fields.direccion,
      web: fields.web,
      email: fields.email,
      telefono: fields.telefono,
      colegioAbogados: fields.colegioAbogados,
      nif: fields.nif,
    },
  });
}

export function logoDespachoPreviewHtml(logoUrl?: string | null): string {
  if (logoUrl) {
    return `<img src="${logoUrl}" alt="" style="display:inline-block;vertical-align:middle;max-height:48px;max-width:100px;object-fit:contain;" />`;
  }

  return '<span style="color:#999;font-size:10px;">[Logo]</span>';
}

export function substituteMembreteVariables(
  html: string,
  values: Record<string, string>,
  logoUrl?: string | null,
): string {
  const withText = substituteVariables(html, values);
  const logoHtml = logoDespachoPreviewHtml(logoUrl);

  return withText.replace(/\[\[LOGO_DESPACHO\]\]/g, logoHtml).replace(/\[\[SELLO_DESPACHO\]\]/g, '[Sello]');
}

export function renderAutoCabeceraHtml(values: Record<string, string>, logoUrl?: string | null): string {
  const logoRaw = logoDespachoPreviewHtml(logoUrl);
  const logo = logoUrl
    ? logoRaw.replace(
        'style="display:inline-block;',
        'style="display:inline-block;background:#2c3e6b;padding:6px 8px;border-radius:4px;',
      )
    : logoRaw;

  return [
    '<table style="width:100%;border-collapse:collapse;"><tr>',
    `<td style="vertical-align:middle;text-align:left;padding-bottom:6px;">`,
    `<div class="membrete-nombre">${substituteVariables('[[NOMBRE_FIRMA]]', values)}</div>`,
    `<div class="membrete-subtitulo">${substituteVariables('[[SUBTITULO_PROFESIONAL]]', values)}</div>`,
    '</td>',
    `<td style="width:72px;vertical-align:middle;text-align:right;padding-bottom:6px;">${logo}</td>`,
    '</tr></table>',
    '<div class="membrete-rule"></div>',
  ].join('');
}

export function renderAutoPieHtml(values: Record<string, string>): string {
  return [
    `<p>${substituteVariables('[[WEB_DESPACHO]]', values)} — ${substituteVariables('[[EMAIL_DESPACHO]]', values)} — ${substituteVariables('[[TELEFONO_DESPACHO]]', values)}</p>`,
    `<p>${substituteVariables('[[COLEGIO_ABOGADOS]]', values)} · NIF ${substituteVariables('[[NIF_LETRADA]]', values)}</p>`,
  ].join('');
}

export function resolveMembreteHtml(
  position: 'cabecera' | 'pie',
  customHtml: string | null | undefined,
  values: Record<string, string>,
  logoUrl?: string | null,
): string {
  const trimmed = customHtml?.trim() ?? '';
  if (trimmed !== '') {
    return substituteMembreteVariables(trimmed, values, logoUrl);
  }

  return position === 'cabecera' ? renderAutoCabeceraHtml(values, logoUrl) : renderAutoPieHtml(values);
}

export function membreteFieldsFromContext(context: VariablePreviewContext): DespachoMembreteFields {
  return {
    nombreFirma: context.despacho?.nombreFirma ?? '',
    subtituloProfesional: context.despacho?.subtituloProfesional ?? '',
    direccion: context.despacho?.direccion ?? '',
    web: context.despacho?.web ?? '',
    email: context.despacho?.email ?? '',
    telefono: context.despacho?.telefono ?? '',
    colegioAbogados: context.despacho?.colegioAbogados ?? '',
    nif: context.despacho?.nif ?? '',
  };
}
