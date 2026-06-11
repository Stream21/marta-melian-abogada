import type { DespachoConfigResponse } from '@/api/client';
import {
  buildDespachoMembreteValues,
  membreteFieldsFromContext,
  resolveMembreteHtml,
} from '@/lib/despacho-membrete';
import type { VariablePreviewContext } from '@/lib/hoja-encargo-variables';
import { cn } from '@/lib/utils';

interface MembretePreviewProps {
  despacho?: DespachoConfigResponse;
  previewContext: VariablePreviewContext;
  position: 'top' | 'bottom';
  logoUrl?: string | null;
  /** Vista previa del documento final: sin marco ni etiqueta de solo lectura */
  documentPreview?: boolean;
}

export function MembretePreview({
  despacho,
  previewContext,
  position,
  logoUrl,
  documentPreview = false,
}: MembretePreviewProps) {
  const fields = membreteFieldsFromContext(previewContext);
  const values = buildDespachoMembreteValues(fields);
  const membretePosition = position === 'top' ? 'cabecera' : 'pie';
  const customHtml = position === 'top' ? despacho?.cabeceraHtml : despacho?.pieHtml;
  const html = resolveMembreteHtml(membretePosition, customHtml, values, logoUrl);

  const htmlClass = 'membrete-html-preview max-w-none text-xs [&_img]:inline-block';

  if (documentPreview) {
    return (
      <div
        className={cn(
          'shrink-0',
          position === 'bottom' ? 'mt-auto pt-6' : 'mb-6',
        )}
      >
        <div className={htmlClass} dangerouslySetInnerHTML={{ __html: html }} />
      </div>
    );
  }

  return (
    <div
      className={cn(
        'rounded-lg border border-dashed border-muted-foreground/30 bg-muted/20 px-4 py-3 text-center text-[10px] text-muted-foreground',
        position === 'top' ? 'mb-4' : 'mt-4',
      )}
    >
      <p className="text-[9px] uppercase tracking-wide">
        {position === 'top' ? 'Cabecera del documento' : 'Pie de página del documento'} (solo lectura —{' '}
        <a href="/config/despacho" className="text-primary underline">
          editar en Configuración del despacho
        </a>
        )
      </p>
      <div className={cn(htmlClass, 'mx-auto mt-1')} dangerouslySetInnerHTML={{ __html: html }} />
      {!despacho?.nombreFirma && (
        <p className="mt-1 text-destructive/80">Configure los datos del despacho para personalizar el membrete.</p>
      )}
    </div>
  );
}
