import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Download, FileArchive, FileText, Filter, ImageIcon, Loader2, PenLine } from 'lucide-react';
import { api, openAuthenticatedDocument, type DocumentacionExpedienteItemResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const FASE_NEGOCIO_LABELS: Record<string, string> = {
  contratacion: 'Contratación',
  requerimientos: 'Requerimientos',
  tramitacion: 'Tramitación',
  resolucion: 'Resolución',
  todos: 'Todas las fases',
};

interface ExpedienteDocumentacionPanelProps {
  expedienteId: string;
}

function downloadBlob(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  window.setTimeout(() => URL.revokeObjectURL(url), 60_000);
}

export function ExpedienteDocumentacionPanel({ expedienteId }: ExpedienteDocumentacionPanelProps) {
  const [filtroFase, setFiltroFase] = useState<string>('todos');
  const [filtroTipo, setFiltroTipo] = useState<string>('todos');
  const [seleccionados, setSeleccionados] = useState<Set<string>>(new Set());
  const [descargandoZip, setDescargandoZip] = useState(false);
  const [zipError, setZipError] = useState<string | null>(null);

  const { data: items = [], isLoading } = useQuery({
    queryKey: ['documentacion', expedienteId],
    queryFn: () => api.getDocumentacionExpediente(expedienteId),
  });

  const fasesDisponibles = useMemo(() => {
    const set = new Set(items.map((i) => i.faseNegocio));
    return ['todos', ...Array.from(set)];
  }, [items]);

  const filtrados = useMemo(() => {
    return items.filter((item) => {
      if (filtroFase !== 'todos' && item.faseNegocio !== filtroFase) return false;
      if (filtroTipo !== 'todos' && item.tipo !== filtroTipo) return false;
      return true;
    });
  }, [items, filtroFase, filtroTipo]);

  const descargables = useMemo(
    () => filtrados.filter((item) => resolveDescargaPath(expedienteId, item) !== null),
    [filtrados, expedienteId],
  );

  const todosSeleccionados =
    descargables.length > 0 && descargables.every((item) => seleccionados.has(item.id));

  const toggleItem = (id: string) => {
    setSeleccionados((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const toggleTodos = () => {
    if (todosSeleccionados) {
      setSeleccionados((prev) => {
        const next = new Set(prev);
        for (const item of descargables) next.delete(item.id);
        return next;
      });
      return;
    }
    setSeleccionados((prev) => {
      const next = new Set(prev);
      for (const item of descargables) next.add(item.id);
      return next;
    });
  };

  const handleDescargarZip = async () => {
    const ids = Array.from(seleccionados).filter((id) =>
      descargables.some((item) => item.id === id),
    );
    if (ids.length === 0) return;

    setDescargandoZip(true);
    setZipError(null);
    try {
      const { blob, filename } = await api.descargarZipDocumentacion(expedienteId, ids);
      downloadBlob(blob, filename);
    } catch (e) {
      setZipError(e instanceof Error ? e.message : 'No se pudo generar el ZIP.');
    } finally {
      setDescargandoZip(false);
    }
  };

  if (isLoading) {
    return <p className="text-muted-foreground py-8 text-center">Cargando documentación…</p>;
  }

  return (
    <div className="panel p-6 space-y-6">
      <div>
        <p className="section-label">Expediente</p>
        <h2 className="panel-title">Documentación y escritos</h2>
        <p className="text-sm text-muted-foreground mt-1">
          Seleccione los documentos que necesite y descárguelos juntos en un ZIP.
        </p>
      </div>

      <div className="flex flex-wrap items-center gap-3">
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
          <Filter className="h-4 w-4" />
          Filtros
        </div>
        <select
          value={filtroFase}
          onChange={(e) => setFiltroFase(e.target.value)}
          className="h-9 rounded-lg border border-input bg-muted/50 px-3 text-sm"
        >
          {fasesDisponibles.map((f) => (
            <option key={f} value={f}>
              {FASE_NEGOCIO_LABELS[f] ?? f}
            </option>
          ))}
        </select>
        <select
          value={filtroTipo}
          onChange={(e) => setFiltroTipo(e.target.value)}
          className="h-9 rounded-lg border border-input bg-muted/50 px-3 text-sm"
        >
          <option value="todos">Documentos y escritos</option>
          <option value="documento">Solo documentos</option>
          <option value="escrito">Solo escritos generados</option>
        </select>
        <Badge variant="secondary">{filtrados.length} elemento(s)</Badge>
      </div>

      {descargables.length > 0 && (
        <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border bg-muted/30 px-4 py-3">
          <label className="flex items-center gap-2 text-sm cursor-pointer select-none">
            <input
              type="checkbox"
              className="h-4 w-4 rounded border-input accent-primary"
              checked={todosSeleccionados}
              onChange={toggleTodos}
            />
            {todosSeleccionados ? 'Quitar selección' : 'Seleccionar todos los descargables'}
            {seleccionados.size > 0 && (
              <span className="text-muted-foreground">({seleccionados.size})</span>
            )}
          </label>
          <div className="flex flex-col items-end gap-1">
            <Button
              size="sm"
              disabled={seleccionados.size === 0 || descargandoZip}
              onClick={() => void handleDescargarZip()}
            >
              {descargandoZip ? (
                <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
              ) : (
                <FileArchive className="mr-1.5 h-3.5 w-3.5" />
              )}
              Descargar ZIP
            </Button>
            {zipError && <span className="text-xs text-destructive max-w-sm text-right">{zipError}</span>}
          </div>
        </div>
      )}

      {filtrados.length === 0 ? (
        <p className="text-sm text-muted-foreground py-8 text-center">No hay elementos con estos filtros.</p>
      ) : (
        <ul className="space-y-3">
          {filtrados.map((item) => (
            <DocumentacionItem
              key={item.id}
              item={item}
              expedienteId={expedienteId}
              seleccionado={seleccionados.has(item.id)}
              onToggle={() => toggleItem(item.id)}
            />
          ))}
        </ul>
      )}
    </div>
  );
}

function resolveDescargaPath(
  expedienteId: string,
  item: DocumentacionExpedienteItemResponse,
): string | null {
  if (item.descargaUrl) {
    return item.descargaUrl;
  }

  const esFirmaElectronica =
    item.origen === 'documento_firmado' || item.origen === 'escrito_firmado';

  if (item.origen === 'identidad_cliente') {
    const lado = item.id === 'identidad-anverso' ? 'anverso' : 'reverso';
    return `/api/expedientes/${encodeURIComponent(expedienteId)}/documentacion/identidad/${lado}`;
  }

  if (esFirmaElectronica) {
    const tipo = item.id.replace(/^firma-/, '');
    return `/api/expedientes/${encodeURIComponent(expedienteId)}/contratacion/firmas/${encodeURIComponent(tipo)}/pdf`;
  }

  if (item.estado === 'entregado' || item.estado === 'validado' || item.estado === 'firmado') {
    return `/api/expedientes/${encodeURIComponent(expedienteId)}/documentacion/${encodeURIComponent(item.id)}/archivo`;
  }

  return null;
}

function DocumentacionItem({
  item,
  expedienteId,
  seleccionado,
  onToggle,
}: {
  item: DocumentacionExpedienteItemResponse;
  expedienteId: string;
  seleccionado: boolean;
  onToggle: () => void;
}) {
  const [abriendo, setAbriendo] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const esImagen = item.mediaTipo === 'imagen';
  const esFirmaElectronica =
    item.origen === 'documento_firmado' || item.origen === 'escrito_firmado';
  const Icon = esImagen ? ImageIcon : esFirmaElectronica ? PenLine : FileText;

  const descargaPath = resolveDescargaPath(expedienteId, item);

  const handleAbrir = async () => {
    if (!descargaPath) return;
    setAbriendo(true);
    setError(null);
    try {
      await openAuthenticatedDocument(descargaPath);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'No se pudo abrir el documento.');
    } finally {
      setAbriendo(false);
    }
  };

  return (
    <li
      className={cn(
        'rounded-lg border bg-card px-4 py-3 text-sm transition-colors',
        seleccionado ? 'border-primary/40 bg-primary/5' : 'border-border',
      )}
    >
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="flex gap-3 min-w-0">
          {descargaPath ? (
            <label className="mt-2 flex shrink-0 cursor-pointer">
              <input
                type="checkbox"
                className="h-4 w-4 rounded border-input accent-primary"
                checked={seleccionado}
                onChange={onToggle}
                aria-label={`Seleccionar ${item.nombre}`}
              />
            </label>
          ) : (
            <span className="mt-2 w-4 shrink-0" aria-hidden />
          )}
          <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted">
            <Icon className="h-4 w-4 text-muted-foreground" />
          </div>
          <div className="min-w-0">
            <p className="font-medium">{item.nombre}</p>
            {item.descripcion && (
              <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">{item.descripcion}</p>
            )}
            <div className="mt-2 flex flex-wrap gap-1.5">
              <Badge variant="info">{item.faseNegocioLabel}</Badge>
              <Badge variant={item.origen === 'requisito_tramite' ? 'secondary' : 'outline'}>
                {item.origenLabel}
              </Badge>
              {item.obligatorio && <Badge variant="warning">Obligatorio</Badge>}
              <EstadoBadge estado={item.estado} />
            </div>
          </div>
        </div>
        <div className="flex flex-col items-end gap-2 shrink-0">
          {item.entregadoAt && (
            <span className="text-xs text-muted-foreground">
              {new Date(item.entregadoAt).toLocaleString('es-ES')}
            </span>
          )}
          {descargaPath && (
            <div className="flex flex-col items-end gap-1">
              <Button size="sm" variant="outline" onClick={() => void handleAbrir()} disabled={abriendo}>
                {abriendo ? (
                  <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                ) : (
                  <Download className="mr-1.5 h-3.5 w-3.5" />
                )}
                {esImagen ? 'Ver imagen' : 'Ver PDF'}
              </Button>
              {error && <span className="text-xs text-destructive max-w-[12rem] text-right">{error}</span>}
            </div>
          )}
        </div>
      </div>
    </li>
  );
}

function EstadoBadge({ estado }: { estado: string }) {
  const variant = estado === 'firmado' || estado === 'validado' ? 'success' : estado === 'entregado' ? 'info' : 'secondary';
  return (
    <Badge variant={variant} className={cn('capitalize')}>
      {estado.replace(/_/g, ' ')}
    </Badge>
  );
}
