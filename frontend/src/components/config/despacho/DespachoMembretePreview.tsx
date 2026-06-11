import {
  buildDespachoMembreteValues,
  resolveMembreteHtml,
  type DespachoMembreteFields,
} from '@/lib/despacho-membrete';

interface DespachoMembretePreviewProps extends DespachoMembreteFields {
  cabeceraHtml: string;
  pieHtml: string;
  logoUrl?: string | null;
}

export function DespachoMembretePreview({
  nombreFirma,
  subtituloProfesional,
  direccion,
  web,
  email,
  telefono,
  colegioAbogados,
  nif,
  cabeceraHtml,
  pieHtml,
  logoUrl,
}: DespachoMembretePreviewProps) {
  const values = buildDespachoMembreteValues({
    nombreFirma,
    subtituloProfesional,
    direccion,
    web,
    email,
    telefono,
    colegioAbogados,
    nif,
  });

  const renderBlock = (position: 'cabecera' | 'pie') => {
    const customHtml = position === 'cabecera' ? cabeceraHtml : pieHtml;
    const html = resolveMembreteHtml(position, customHtml, values, logoUrl);

    return (
      <div className="rounded-lg border border-dashed border-muted-foreground/30 bg-muted/20 px-4 py-3 text-center">
        <p className="mb-2 text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
          {position === 'cabecera' ? 'Cabecera (todas las páginas)' : 'Pie de página (todas las páginas)'}
        </p>
        <div
          className="membrete-html-preview max-w-none text-xs [&_img]:inline-block"
          dangerouslySetInnerHTML={{ __html: html }}
        />
      </div>
    );
  };

  return (
    <div className="space-y-3">
      {renderBlock('cabecera')}
      {renderBlock('pie')}
    </div>
  );
}
