import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Download, Eye, FileText } from 'lucide-react';
import { api, type ExpedienteResponse } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { TIPOS_ESCRITO, type TipoEscrito } from '@/lib/hoja-encargo-variables';

interface EscritoGeneradorPanelProps {
  expediente: ExpedienteResponse;
}

export function EscritoGeneradorPanel({ expediente }: EscritoGeneradorPanelProps) {
  const queryClient = useQueryClient();
  const [tipo, setTipo] = useState<TipoEscrito>('hoja_encargo');
  const [incluirMembrete, setIncluirMembrete] = useState(true);
  const [previewHtml, setPreviewHtml] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [tramiteId, setTramiteId] = useState<string | null>(expediente.tramiteId ?? null);
  const [clienteId, setClienteId] = useState<string | null>(expediente.clienteId ?? null);

  useEffect(() => {
    setTramiteId(expediente.tramiteId ?? null);
    setClienteId(expediente.clienteId ?? null);
  }, [expediente.tramiteId, expediente.clienteId]);

  const { data: tramites = [] } = useQuery({
    queryKey: ['tramites'],
    queryFn: () => api.getTramites(),
  });

  const { data: clientes = [] } = useQuery({
    queryKey: ['clientes'],
    queryFn: () => api.getClientes(),
  });

  const vincularMutation = useMutation({
    mutationFn: (body: { clienteId?: string | null; tramiteId?: string | null }) =>
      api.vincularExpediente(expediente.id, body),
    onSuccess: (_, variables) => {
      if (variables.tramiteId !== undefined) setTramiteId(variables.tramiteId);
      if (variables.clienteId !== undefined) setClienteId(variables.clienteId);
      void queryClient.invalidateQueries({ queryKey: ['expedientes'] });
    },
  });

  const previewMutation = useMutation({
    mutationFn: () => api.previewEscrito(expediente.id, tipo, incluirMembrete),
    onSuccess: (data) => {
      setPreviewHtml(data.html);
      setError(null);
    },
    onError: (err: Error) => {
      setError(err.message);
      setPreviewHtml(null);
    },
  });

  const pdfMutation = useMutation({
    mutationFn: () => api.downloadEscritoPdf(expediente.id, tipo, incluirMembrete),
    onSuccess: (blob) => {
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${tipo}_${expediente.numero}.pdf`;
      a.click();
      URL.revokeObjectURL(url);
      setError(null);
    },
    onError: (err: Error) => setError(err.message),
  });

  const isPending = previewMutation.isPending || pdfMutation.isPending || vincularMutation.isPending;

  const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';

  return (
    <div className="panel max-w-3xl">
      <div className="panel-header">
        <div className="panel-header-icon">
          <FileText className="h-5 w-5" />
        </div>
        <div>
          <h2 className="panel-title">Generar escrito</h2>
          <p className="text-sm text-muted-foreground">
            Genera documentos PDF a partir de la plantilla del trámite y los datos del cliente.
          </p>
        </div>
      </div>

      <div className="space-y-6 p-6">
        {error && (
          <p className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {error}
          </p>
        )}

        <div className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="escrito-tramite">Trámite asociado</Label>
            <select
              id="escrito-tramite"
              className={selectClass}
              value={tramiteId ?? ''}
              onChange={(e) =>
                vincularMutation.mutate({
                  tramiteId: e.target.value || null,
                  clienteId: clienteId,
                })
              }
              disabled={isPending}
            >
              <option value="">Seleccionar trámite</option>
              {tramites.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.nombre}
                </option>
              ))}
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="escrito-cliente">Cliente</Label>
            <select
              id="escrito-cliente"
              className={selectClass}
              value={clienteId ?? ''}
              onChange={(e) =>
                vincularMutation.mutate({
                  clienteId: e.target.value || null,
                  tramiteId: tramiteId,
                })
              }
              disabled={isPending}
            >
              <option value="">Seleccionar cliente</option>
              {clientes.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.nombre}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="escrito-tipo">Tipo de escrito</Label>
            <select
              id="escrito-tipo"
              className={selectClass}
              value={tipo}
              onChange={(e) => setTipo(e.target.value as TipoEscrito)}
              disabled={isPending}
            >
              {TIPOS_ESCRITO.map((t) => (
                <option key={t.value} value={t.value}>
                  {t.label}
                </option>
              ))}
            </select>
          </div>

          <div className="flex items-end pb-2">
            <label className="flex cursor-pointer items-center gap-2">
              <input
                type="checkbox"
                checked={incluirMembrete}
                onChange={(e) => setIncluirMembrete(e.target.checked)}
                disabled={isPending}
                className="h-4 w-4 rounded border-input"
              />
              <span className="text-sm">Incluir membrete (cabecera y pie)</span>
            </label>
          </div>
        </div>

        <div className="flex flex-wrap gap-2">
          <Button
            type="button"
            variant="outline"
            onClick={() => previewMutation.mutate()}
            disabled={isPending || !tramiteId}
          >
            <Eye className="h-4 w-4" />
            Vista previa
          </Button>
          <Button
            type="button"
            onClick={() => pdfMutation.mutate()}
            disabled={isPending || !tramiteId}
          >
            <Download className="h-4 w-4" />
            Descargar PDF
          </Button>
        </div>

        {previewHtml && (
          <div className="overflow-hidden rounded-lg border border-border">
            <iframe
              title="Vista previa del escrito"
              srcDoc={previewHtml}
              className="h-[600px] w-full bg-white"
              sandbox="allow-same-origin"
            />
          </div>
        )}
      </div>
    </div>
  );
}
