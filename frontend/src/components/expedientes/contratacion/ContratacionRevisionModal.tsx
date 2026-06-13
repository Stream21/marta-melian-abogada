import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { api, type ContratacionPasoResponse } from '@/api/client';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { ClienteDatosRevisionPanel } from './ClienteDatosRevisionPanel';
import { FirmasRevisionPanel } from './FirmasRevisionPanel';
import { ContratacionPdfEmbed } from './ContratacionPdfEmbed';
import { formatEuros, getImportePagoInicial } from '@/lib/pago-contratacion';

interface ContratacionRevisionModalProps {
  expedienteId: string;
  paso: ContratacionPasoResponse | null;
  open: boolean;
  onClose: () => void;
  onValidar: (paso: string) => void;
  onDevolver: (paso: string, nota: string) => void;
  validando: boolean;
  devolviendo: boolean;
  errorAccion?: string | null;
}

export function ContratacionRevisionModal({
  expedienteId,
  paso,
  open,
  onClose,
  onValidar,
  onDevolver,
  validando,
  devolviendo,
  errorAccion,
}: ContratacionRevisionModalProps) {
  const [nota, setNota] = useState('');
  const [mostrarDevolucion, setMostrarDevolucion] = useState(false);

  useEffect(() => {
    if (!open) {
      setNota('');
      setMostrarDevolucion(false);
    }
  }, [open, paso?.paso]);

  const { data: documentos } = useQuery({
    queryKey: ['contratacion-documentos', expedienteId],
    queryFn: () => api.getContratacionDocumentos(expedienteId),
    enabled: open && paso?.paso === 'datos_cliente',
  });

  const { data: contratacion } = useQuery({
    queryKey: ['contratacion', expedienteId],
    queryFn: () => api.getContratacion(expedienteId),
    enabled: open && !!paso,
  });

  if (!paso) return null;

  const docsEntregados = (documentos ?? []).filter((d) => d.archivoPath);
  const procesando = validando || devolviendo;
  const esPagoManual = paso.paso === 'pago' && contratacion?.metodoPago === 'manual';
  const importePagoInicial = contratacion
    ? getImportePagoInicial(contratacion)
    : 0;
  const planLabel =
    contratacion?.planPago === 'fraccionado'
      ? `Fraccionado (${contratacion.numCuotas} cuotas)`
      : 'Pago único';

  return (
    <Dialog open={open} onOpenChange={(v) => !v && onClose()}>
      <DialogContent
        className={`${paso.paso === 'datos_cliente' ? 'max-w-4xl' : 'max-w-3xl'} max-h-[90vh] overflow-y-auto`}
      >
        <DialogHeader>
          <DialogTitle>Revisar: {paso.label}</DialogTitle>
          <DialogDescription>
            Previsualice la documentación y valide el paso, o devuélvalo al cliente con una nota explicativa.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-5 py-2">
          {paso.paso === 'datos_cliente' && (
            <>
              {contratacion?.clienteId && <ClienteDatosRevisionPanel clienteId={contratacion.clienteId} />}
              {docsEntregados.length > 0 && (
                <div className="space-y-4">
                  <p className="section-label">Documentación adicional</p>
                  {docsEntregados.map((doc) => (
                    <div key={doc.id}>
                      <p className="mb-2 text-sm font-medium">{doc.nombre}</p>
                      <ContratacionPdfEmbed
                        url={api.contratacionDocumentoArchivoUrl(expedienteId, doc.id)}
                        title={doc.nombre}
                      />
                    </div>
                  ))}
                </div>
              )}
            </>
          )}

          {paso.paso === 'firmas' && (
            contratacion?.firmasDocumento ? (
              <FirmasRevisionPanel
                expedienteId={expedienteId}
                firmas={contratacion.firmasDocumento}
              />
            ) : (
              <p className="text-sm text-muted-foreground py-4 text-center">Cargando documentos…</p>
            )
          )}

          {paso.paso === 'pago' && contratacion && (
            <div className="rounded-lg border bg-muted/30 p-4 text-sm space-y-2">
              <p>
                <strong>Importe a cobrar ahora:</strong>{' '}
                <span className="font-semibold text-primary">{formatEuros(importePagoInicial)}</span>
              </p>
              {contratacion.planPago === 'fraccionado' && (
                <>
                  <p>
                    <strong>Honorarios totales:</strong>{' '}
                    {formatEuros(contratacion.honorariosAcordados)}
                  </p>
                  <p>
                    <strong>Plan:</strong> {planLabel}
                  </p>
                </>
              )}
              <p>
                <strong>Método:</strong> {contratacion.metodoPagoLabel}
              </p>
              <p className="text-muted-foreground">
                {esPagoManual
                  ? 'Confirme que ha recibido el cobro inicial (efectivo, Bizum, transferencia u otro medio acordado) antes de validar.'
                  : 'Confirme que el pago se ha recibido correctamente antes de validar.'}
              </p>
            </div>
          )}

          {mostrarDevolucion && (
            <div className="rounded-lg border border-amber-200 bg-amber-50/50 p-4 space-y-3">
              <Label htmlFor="nota-devolucion" className="text-sm font-medium">
                Nota para el cliente
              </Label>
              <textarea
                id="nota-devolucion"
                className="input-field min-h-[100px] w-full resize-y"
                placeholder="Indique qué debe corregir o revisar el cliente…"
                value={nota}
                onChange={(e) => setNota(e.target.value)}
                maxLength={1000}
              />
              <p className="text-xs text-muted-foreground">Mínimo 5 caracteres. El cliente verá este mensaje en su portal.</p>
            </div>
          )}

          {errorAccion && (
            <p className="text-sm text-destructive">{errorAccion}</p>
          )}
        </div>

        <DialogFooter className="flex-col gap-2 sm:flex-row sm:justify-between">
          <div className="flex flex-wrap gap-2">
            {!mostrarDevolucion && !esPagoManual ? (
              <Button
                type="button"
                variant="outline"
                className="border-amber-300 text-amber-800 hover:bg-amber-50"
                onClick={() => setMostrarDevolucion(true)}
                disabled={procesando}
              >
                Devolver al cliente
              </Button>
            ) : (
              <>
                <Button
                  type="button"
                  variant="ghost"
                  onClick={() => {
                    setMostrarDevolucion(false);
                    setNota('');
                  }}
                  disabled={procesando}
                >
                  Cancelar devolución
                </Button>
                <Button
                  type="button"
                  variant="destructive"
                  onClick={() => onDevolver(paso.paso, nota.trim())}
                  disabled={procesando || nota.trim().length < 5}
                >
                  {devolviendo ? 'Enviando…' : 'Enviar nota y devolver'}
                </Button>
              </>
            )}
          </div>
          <div className="flex gap-2">
            <Button variant="outline" onClick={onClose} disabled={procesando}>
              Cerrar
            </Button>
            <Button onClick={() => onValidar(paso.paso)} disabled={procesando || mostrarDevolucion}>
              {validando ? 'Validando…' : paso.paso === 'pago' ? 'Confirmar pago recibido' : 'Validar y continuar'}
            </Button>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
