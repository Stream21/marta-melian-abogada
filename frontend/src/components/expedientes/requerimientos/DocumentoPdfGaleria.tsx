import { useEffect, useState } from 'react';
import { ChevronLeft, ChevronRight, FileText } from 'lucide-react';
import { ContratacionPdfEmbed } from '@/components/expedientes/contratacion/ContratacionPdfEmbed';
import { Button } from '@/components/ui/button';
import type { DocumentoArchivoItem } from '@/components/cliente-portal/DocumentoArchivosSubidosList';
import { cn } from '@/lib/utils';

interface DocumentoPdfGaleriaProps {
  archivos: DocumentoArchivoItem[];
  buildUrl: (archivoId: string) => string;
  title?: string;
  className?: string;
}

export function DocumentoPdfGaleria({
  archivos,
  buildUrl,
  title,
  className,
}: DocumentoPdfGaleriaProps) {
  const [indice, setIndice] = useState(0);

  useEffect(() => {
    setIndice(0);
  }, [archivos.map((archivo) => archivo.id).join('|')]);

  if (archivos.length === 0) {
    return null;
  }

  const actual = archivos[indice] ?? archivos[0];
  const url = buildUrl(actual.id);
  const multiples = archivos.length > 1;
  const total = archivos.length;

  const irAnterior = () => setIndice((prev) => Math.max(0, prev - 1));
  const irSiguiente = () => setIndice((prev) => Math.min(total - 1, prev + 1));

  return (
    <div className={cn('space-y-3', className)}>
      {multiples && (
        <>
          {/* Móvil y tablet: selector nativo + navegación compacta */}
          <div className="flex items-center gap-2 lg:hidden">
            <Button
              type="button"
              variant="outline"
              size="icon"
              className="h-9 w-9 shrink-0"
              disabled={indice === 0}
              onClick={irAnterior}
              aria-label="Archivo anterior"
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <select
              className="input-field h-9 min-w-0 flex-1 truncate py-1 text-sm"
              value={indice}
              onChange={(e) => setIndice(Number(e.target.value))}
              aria-label="Seleccionar archivo"
            >
              {archivos.map((archivo, index) => (
                <option key={archivo.id} value={index}>
                  {index + 1}. {archivo.nombre}
                </option>
              ))}
            </select>
            <Button
              type="button"
              variant="outline"
              size="icon"
              className="h-9 w-9 shrink-0"
              disabled={indice >= total - 1}
              onClick={irSiguiente}
              aria-label="Archivo siguiente"
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
          <p className="text-xs text-muted-foreground lg:hidden">
            Archivo {indice + 1} de {total}
          </p>
        </>
      )}

      <div className="flex flex-col gap-3 lg:flex-row lg:items-start">
        {/* Escritorio: lista vertical desplazable */}
        {multiples && (
          <aside className="hidden lg:block lg:w-52 xl:w-60 shrink-0">
            <p className="mb-2 text-xs font-medium text-muted-foreground">
              {total} archivo{total === 1 ? '' : 's'}
            </p>
            <ul className="max-h-[min(420px,50vh)] space-y-1 overflow-y-auto rounded-lg border border-border bg-muted/20 p-1.5">
              {archivos.map((archivo, index) => {
                const activo = index === indice;
                return (
                  <li key={archivo.id}>
                    <button
                      type="button"
                      className={cn(
                        'flex w-full items-start gap-2 rounded-md px-2.5 py-2 text-left text-sm transition-colors',
                        activo
                          ? 'bg-primary text-primary-foreground'
                          : 'text-foreground hover:bg-muted',
                      )}
                      onClick={() => setIndice(index)}
                    >
                      <FileText
                        className={cn(
                          'mt-0.5 h-4 w-4 shrink-0',
                          activo ? 'text-primary-foreground' : 'text-muted-foreground',
                        )}
                        aria-hidden
                      />
                      <span className="min-w-0 flex-1 break-words leading-snug">
                        <span className="mr-1 font-medium">{index + 1}.</span>
                        {archivo.nombre}
                      </span>
                    </button>
                  </li>
                );
              })}
            </ul>
            <div className="mt-2 flex items-center justify-between gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={indice === 0}
                onClick={irAnterior}
              >
                <ChevronLeft className="mr-1 h-4 w-4" />
                Anterior
              </Button>
              <span className="text-xs text-muted-foreground">
                {indice + 1}/{total}
              </span>
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={indice >= total - 1}
                onClick={irSiguiente}
              >
                Siguiente
                <ChevronRight className="ml-1 h-4 w-4" />
              </Button>
            </div>
          </aside>
        )}

        <div className="min-w-0 flex-1">
          {multiples && (
            <p className="mb-2 hidden truncate text-sm font-medium text-foreground lg:block">
              {actual.nombre}
            </p>
          )}
          <ContratacionPdfEmbed
            url={url}
            title={title ?? actual.nombre}
            className="min-h-[280px] h-[min(420px,55vh)] sm:min-h-[320px] sm:h-[min(480px,60vh)]"
          />
        </div>
      </div>
    </div>
  );
}
