export interface DocumentoArchivoItem {
  id: string;
  nombre: string;
  orden?: number;
}

interface DocumentoArchivosSubidosListProps {
  archivos: DocumentoArchivoItem[];
  className?: string;
}

export function DocumentoArchivosSubidosList({ archivos, className }: DocumentoArchivosSubidosListProps) {
  if (archivos.length === 0) {
    return null;
  }

  return (
    <div className={className}>
      <p className="text-xs font-medium text-muted-foreground">
        Archivos aportados ({archivos.length})
      </p>
      <ul className="mt-1.5 space-y-1">
        {archivos.map((archivo, index) => (
          <li
            key={archivo.id}
            className="flex items-center gap-2 rounded-md border border-border/60 bg-muted/20 px-2.5 py-1.5 text-xs"
          >
            <span className="shrink-0 font-medium text-muted-foreground">{index + 1}.</span>
            <span className="min-w-0 truncate">{archivo.nombre}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}
