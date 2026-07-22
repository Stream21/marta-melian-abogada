import { useEffect, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
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
import { AbogadoContratacionIdentidadCarga } from './AbogadoContratacionIdentidadCarga';
import { FirmasRevisionPanel } from './FirmasRevisionPanel';
import { ContratacionPdfEmbed } from './ContratacionPdfEmbed';
import { formatEuros, getImportePagoInicial } from '@/lib/pago-contratacion';
import {
  combinarMotivosDevolucion,
  ETIQUETAS_CAMPO_CLIENTE,
  motivoFirmaDevolucion,
} from '@/lib/campos-devolucion';

interface ContratacionRevisionModalProps {
  expedienteId: string;
  paso: ContratacionPasoResponse | null;
  open: boolean;
  onClose: () => void;
  onValidar: (paso: string) => void;
  onDevolver: (paso: string, nota: string, motivos?: string[]) => void;
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
  const queryClient = useQueryClient();
  const [nota, setNota] = useState('');
  const [mostrarDevolucion, setMostrarDevolucion] = useState(false);
  const [motivos, setMotivos] = useState<string[]>([]);
  const [camposMarcados, setCamposMarcados] = useState<string[]>([]);
  const [firmasARefirmar, setFirmasARefirmar] = useState<string[]>([]);

  const MOTIVOS_DATOS_CLIENTE: { id: string; label: string }[] = [
    { id: 'documento_anverso', label: 'Nueva foto del anverso del documento' },
    { id: 'documento_reverso', label: 'Nueva foto del reverso (MRZ)' },
    { id: 'documento_completo', label: 'Actualizar documento completo' },
    { id: 'documentacion_adicional', label: 'Documentación adicional del trámite' },
  ];

  useEffect(() => {
    if (!open) {
      setNota('');
      setMostrarDevolucion(false);
      setMotivos([]);
      setCamposMarcados([]);
      setFirmasARefirmar([]);
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
  const firmasFirmadas = (contratacion?.firmasDocumento ?? []).filter((f) => f.firmado);
  const motivosDocumentoOpciones = MOTIVOS_DATOS_CLIENTE.filter(
    (m) => m.id !== 'documentacion_adicional' || docsEntregados.length > 0,
  );
  const motivosDevolucionPayload =
    paso.paso === 'firmas'
      ? firmasARefirmar.map(motivoFirmaDevolucion)
      : combinarMotivosDevolucion(motivos, camposMarcados);
  const puedeEnviarDevolucion =
    nota.trim().length >= 1
    && (
      paso.paso !== 'firmas'
      || firmasFirmadas.length === 0
      || firmasARefirmar.length > 0
      || esPagoManual
    );
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
        onPointerDownOutside={(e) => e.preventDefault()}
        onInteractOutside={(e) => e.preventDefault()}
        onEscapeKeyDown={(e) => {
          // Si hay lightbox abierto, su listener en capture ya cierra la imagen;
          // evitamos que Escape cierre también este modal.
          if (document.querySelector('[data-image-lightbox]')) {
            e.preventDefault();
          }
        }}
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
              <AbogadoContratacionIdentidadCarga
                expedienteId={expedienteId}
                onGuardado={() => {
                  void queryClient.invalidateQueries({ queryKey: ['contratacion', expedienteId] });
                  if (contratacion?.clienteId) {
                    void queryClient.invalidateQueries({ queryKey: ['cliente', contratacion.clienteId] });
                  }
                }}
              />
              {contratacion?.clienteId && (
                <ClienteDatosRevisionPanel
                  expedienteId={expedienteId}
                  clienteId={contratacion.clienteId}
                  camposMarcados={camposMarcados}
                  onCamposMarcadosChange={setCamposMarcados}
                />
              )}
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
              {paso.paso === 'datos_cliente' && (
                <div className="space-y-2">
                  <p className="text-sm font-medium text-amber-900">¿Qué debe corregir el cliente?</p>
                  {camposMarcados.length > 0 && (
                    <div className="rounded-md border border-amber-200 bg-white/70 px-3 py-2">
                      <p className="text-xs font-medium text-amber-900">Campos marcados en la ficha:</p>
                      <ul className="mt-1.5 flex flex-wrap gap-1.5">
                        {camposMarcados.map((k) => (
                          <li
                            key={k}
                            className="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-medium text-amber-950"
                          >
                            {ETIQUETAS_CAMPO_CLIENTE[k] ?? k}
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                  <div className="grid gap-2 sm:grid-cols-2">
                    {motivosDocumentoOpciones.map((m) => (
                      <label
                        key={m.id}
                        className="flex cursor-pointer items-start gap-2 rounded-md border border-amber-200/80 bg-white/60 px-3 py-2 text-sm"
                      >
                        <input
                          type="checkbox"
                          className="mt-0.5"
                          checked={motivos.includes(m.id)}
                          onChange={(e) => {
                            setMotivos((prev) =>
                              e.target.checked ? [...prev, m.id] : prev.filter((x) => x !== m.id),
                            );
                          }}
                        />
                        <span>{m.label}</span>
                      </label>
                    ))}
                  </div>
                  <p className="text-xs text-amber-800/80">
                    Los campos de la ficha se marcan arriba con «Pedir al cliente». Aquí solo la
                    documentación e imágenes.
                  </p>
                </div>
              )}

              {paso.paso === 'firmas' && (
                <div className="space-y-2">
                  <p className="text-sm font-medium text-amber-900">
                    ¿Qué documento(s) debe volver a firmar?
                  </p>
                  {firmasFirmadas.length === 0 ? (
                    <p className="text-xs text-amber-800/90">
                      Aún no hay documentos firmados que invalidar. Puede enviar solo la nota si el
                      cliente debe completar firmas pendientes.
                    </p>
                  ) : (
                    <div className="grid gap-2">
                      {firmasFirmadas.map((f) => (
                        <label
                          key={f.tipo}
                          className="flex cursor-pointer items-start gap-2 rounded-md border border-amber-200/80 bg-white/60 px-3 py-2 text-sm"
                        >
                          <input
                            type="checkbox"
                            className="mt-0.5"
                            checked={firmasARefirmar.includes(f.tipo)}
                            onChange={(e) => {
                              setFirmasARefirmar((prev) =>
                                e.target.checked
                                  ? [...prev, f.tipo]
                                  : prev.filter((x) => x !== f.tipo),
                              );
                            }}
                          />
                          <span>
                            Volver a firmar <strong>{f.label}</strong>
                          </span>
                        </label>
                      ))}
                    </div>
                  )}
                  <p className="text-xs text-amber-800/80">
                    Los documentos marcados dejarán de constar como firmados y el cliente deberá
                    firmarlos otra vez en su portal.
                  </p>
                </div>
              )}

              <Label htmlFor="nota-devolucion" className="text-sm font-medium">
                {esPagoManual ? 'Mensaje para el cliente' : 'Nota para el cliente'}
              </Label>
              <textarea
                id="nota-devolucion"
                className="input-field min-h-[100px] w-full resize-y"
                placeholder={
                  esPagoManual
                    ? 'Indique si ha tenido algún problema con el pago o qué debe hacer el cliente…'
                    : paso.paso === 'firmas'
                      ? 'Explique el error o el motivo por el que debe firmar de nuevo…'
                      : 'Indique qué debe corregir o revisar el cliente…'
                }
                value={nota}
                onChange={(e) => setNota(e.target.value)}
                maxLength={1000}
              />
              <p className="text-xs text-muted-foreground">
                Mínimo 1 carácter. El cliente recibirá un correo y verá este mensaje en su portal.
              </p>
            </div>
          )}

          {errorAccion && (
            <p className="text-sm text-destructive">{errorAccion}</p>
          )}
        </div>

        <DialogFooter className="flex-col gap-2 sm:flex-row sm:justify-between">
          <div className="flex flex-wrap gap-2">
            {!mostrarDevolucion ? (
              esPagoManual ? (
                <Button
                  type="button"
                  variant="outline"
                  className="border-amber-300 text-amber-800 hover:bg-amber-50"
                  onClick={() => setMostrarDevolucion(true)}
                  disabled={procesando}
                >
                  Notificar al cliente
                </Button>
              ) : (
                <Button
                  type="button"
                  variant="outline"
                  className="border-amber-300 text-amber-800 hover:bg-amber-50"
                  onClick={() => setMostrarDevolucion(true)}
                  disabled={procesando}
                >
                  Devolver al cliente
                </Button>
              )
            ) : (
              <>
                <Button
                  type="button"
                  variant="ghost"
                  onClick={() => {
                    setMostrarDevolucion(false);
                    setNota('');
                    setMotivos([]);
                    setFirmasARefirmar([]);
                  }}
                  disabled={procesando}
                >
                  Cancelar
                </Button>
                <Button
                  type="button"
                  variant="destructive"
                  onClick={() =>
                    onDevolver(paso.paso, nota.trim(), motivosDevolucionPayload)
                  }
                  disabled={procesando || !puedeEnviarDevolucion}
                >
                  {devolviendo
                    ? 'Enviando…'
                    : esPagoManual
                      ? 'Enviar nota al cliente'
                      : 'Enviar nota y devolver'}
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
