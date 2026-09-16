import { useState, type FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from '@tanstack/react-router';
import { FileText, Save, Trash2 } from 'lucide-react';
import {
  api,
  openAuthenticatedDocument,
  type GastoItem,
  type GastoPayload,
} from '@/api/client';
import { DocumentoArchivoUploadControl } from '@/components/cliente-portal/DocumentoArchivoUploadControl';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

export type GastoFormMode = 'create' | 'edit';

export interface GastoFormProps {
  mode: GastoFormMode;
  gastoId?: string;
  initial?: Partial<GastoItem>;
}

export function GastoForm({ mode, gastoId, initial }: GastoFormProps) {
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const [concepto, setConcepto] = useState(initial?.concepto ?? '');
  const [importe, setImporte] = useState(initial?.importe ?? '');
  const [fecha, setFecha] = useState(initial?.fecha ?? new Date().toISOString().slice(0, 10));
  const [categoria, setCategoria] = useState(initial?.categoria ?? '');
  const [notas, setNotas] = useState(initial?.notas ?? '');
  const [tieneFactura, setTieneFactura] = useState(initial?.tieneFactura ?? false);
  const [facturaUrl, setFacturaUrl] = useState(initial?.facturaUrl ?? null);
  const [conceptoError, setConceptoError] = useState(false);
  const [importeError, setImporteError] = useState(false);
  const [fechaError, setFechaError] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const [uploadSuccessKey, setUploadSuccessKey] = useState(0);

  const payload = (): GastoPayload => ({
    concepto: concepto.trim(),
    importe: importe.trim().replace(',', '.'),
    fecha,
    categoria: categoria.trim() || null,
    notas: notas.trim() || null,
  });

  const validate = () => {
    const cErr = concepto.trim() === '';
    const iErr = !importe.trim() || Number.isNaN(Number(importe.replace(',', '.'))) || Number(importe.replace(',', '.')) <= 0;
    const fErr = fecha.trim() === '';
    setConceptoError(cErr);
    setImporteError(iErr);
    setFechaError(fErr);
    return !cErr && !iErr && !fErr;
  };

  const invalidate = (id?: string) => {
    void queryClient.invalidateQueries({ queryKey: ['gastos'] });
    if (id) void queryClient.invalidateQueries({ queryKey: ['gasto', id] });
  };

  const createMutation = useMutation({
    mutationFn: () => api.postGasto(payload()),
    onSuccess: (gasto) => {
      invalidate();
      navigate({ to: '/gastos/$gastoId', params: { gastoId: gasto.id } } as never);
    },
  });

  const updateMutation = useMutation({
    mutationFn: () => {
      if (!gastoId) throw new Error('Falta el identificador.');
      return api.putGasto(gastoId, payload());
    },
    onSuccess: () => {
      invalidate(gastoId);
      navigate({ to: '/gastos' } as never);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: () => {
      if (!gastoId) throw new Error('Falta el identificador.');
      return api.deleteGasto(gastoId);
    },
    onSuccess: () => {
      invalidate(gastoId);
      navigate({ to: '/gastos' } as never);
    },
  });

  const uploadMutation = useMutation({
    mutationFn: (file: File) => {
      if (!gastoId) throw new Error('Guarde el gasto antes de adjuntar la factura.');
      return api.subirFacturaGasto(gastoId, file);
    },
    onSuccess: (gasto) => {
      setTieneFactura(gasto.tieneFactura);
      setFacturaUrl(gasto.facturaUrl);
      setUploadError(null);
      setUploadSuccessKey((k) => k + 1);
      invalidate(gastoId);
    },
    onError: (err: Error) => setUploadError(err.message || 'No se pudo subir la factura.'),
  });

  const removeFacturaMutation = useMutation({
    mutationFn: () => {
      if (!gastoId) throw new Error('Falta el identificador.');
      return api.eliminarFacturaGasto(gastoId);
    },
    onSuccess: (gasto) => {
      setTieneFactura(gasto.tieneFactura);
      setFacturaUrl(gasto.facturaUrl);
      invalidate(gastoId);
    },
  });

  const pending =
    createMutation.isPending ||
    updateMutation.isPending ||
    deleteMutation.isPending;

  const errorMessage =
    (createMutation.error as Error | null)?.message ||
    (updateMutation.error as Error | null)?.message ||
    (deleteMutation.error as Error | null)?.message ||
    null;

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!validate()) return;
    if (mode === 'create') createMutation.mutate();
    else updateMutation.mutate();
  };

  return (
    <form onSubmit={onSubmit} className="panel space-y-6 p-6">
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="sm:col-span-2 space-y-2">
          <Label htmlFor="concepto">Concepto</Label>
          <Input
            id="concepto"
            value={concepto}
            onChange={(e) => setConcepto(e.target.value)}
            className={conceptoError ? 'border-destructive' : undefined}
            placeholder="Ej. Material de oficina"
          />
          {conceptoError && (
            <p className="text-xs text-destructive">El concepto es obligatorio.</p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="importe">Importe (€)</Label>
          <Input
            id="importe"
            inputMode="decimal"
            value={importe}
            onChange={(e) => setImporte(e.target.value)}
            className={importeError ? 'border-destructive' : undefined}
            placeholder="0.00"
          />
          {importeError && (
            <p className="text-xs text-destructive">Indique un importe mayor que cero.</p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="fecha">Fecha</Label>
          <Input
            id="fecha"
            type="date"
            value={fecha}
            onChange={(e) => setFecha(e.target.value)}
            className={fechaError ? 'border-destructive' : undefined}
          />
          {fechaError && <p className="text-xs text-destructive">La fecha es obligatoria.</p>}
        </div>

        <div className="sm:col-span-2 space-y-2">
          <Label htmlFor="categoria">Categoría (opcional)</Label>
          <Input
            id="categoria"
            value={categoria}
            onChange={(e) => setCategoria(e.target.value)}
            placeholder="Ej. Viajes, Material, Software…"
          />
        </div>

        <div className="sm:col-span-2 space-y-2">
          <Label htmlFor="notas">Notas (opcional)</Label>
          <textarea
            id="notas"
            value={notas}
            onChange={(e) => setNotas(e.target.value)}
            rows={3}
            placeholder="Observaciones internas"
            className={cn(
              'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
              'ring-offset-background placeholder:text-muted-foreground',
              'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
              'disabled:cursor-not-allowed disabled:opacity-50',
            )}
          />
        </div>
      </div>

      {mode === 'edit' && gastoId && (
        <>
          <Separator />
          <div className="space-y-3">
            <div>
              <p className="section-label">Factura</p>
              <p className="mt-1 text-sm text-muted-foreground">
                Adjunto opcional (PDF o imagen). No es obligatorio.
              </p>
            </div>

            {tieneFactura && facturaUrl ? (
              <div className="flex flex-wrap items-center gap-2">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  className="gap-1.5"
                  onClick={() => void openAuthenticatedDocument(facturaUrl)}
                >
                  <FileText className="h-4 w-4" />
                  Ver factura
                </Button>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="gap-1.5 text-destructive"
                  disabled={removeFacturaMutation.isPending}
                  onClick={() => removeFacturaMutation.mutate()}
                >
                  <Trash2 className="h-4 w-4" />
                  Quitar
                </Button>
              </div>
            ) : null}

            <DocumentoArchivoUploadControl
              tipo="individual"
              maxImagenes={1}
              uploading={uploadMutation.isPending}
              error={uploadError}
              submitLabel={tieneFactura ? 'Sustituir factura' : 'Adjuntar factura'}
              readyLabel="Subir factura"
              uploadSuccessKey={uploadSuccessKey}
              onUpload={(files) => {
                const file = files[0];
                if (file) uploadMutation.mutate(file);
              }}
            />
          </div>
        </>
      )}

      {mode === 'create' && (
        <p className="text-sm text-muted-foreground">
          Tras guardar el gasto podrá adjuntar la factura en la ficha de edición.
        </p>
      )}

      {errorMessage && <p className="text-sm text-destructive">{errorMessage}</p>}

      <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4">
        <div>
          {mode === 'edit' && (
            <Button
              type="button"
              variant="ghost"
              className="gap-1.5 text-destructive"
              disabled={pending}
              onClick={() => {
                if (window.confirm('¿Eliminar este gasto? Esta acción no se puede deshacer.')) {
                  deleteMutation.mutate();
                }
              }}
            >
              <Trash2 className="h-4 w-4" />
              Eliminar
            </Button>
          )}
        </div>
        <div className="flex gap-2">
          <Button
            type="button"
            variant="outline"
            disabled={pending}
            onClick={() => navigate({ to: '/gastos' } as never)}
          >
            Cancelar
          </Button>
          <Button type="submit" disabled={pending} className="gap-1.5">
            <Save className="h-4 w-4" />
            {mode === 'create' ? 'Crear gasto' : 'Guardar cambios'}
          </Button>
        </div>
      </div>
    </form>
  );
}
