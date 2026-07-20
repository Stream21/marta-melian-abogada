import { useEffect, useState } from 'react';
import type { RequerimientosDocumentoResponse } from '@/api/client';
import { DocumentoPdfGaleria } from '@/components/expedientes/requerimientos/DocumentoPdfGaleria';
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

interface RequerimientosDocumentoRevisionModalProps {
  doc: RequerimientosDocumentoResponse | null;
  open: boolean;
  onClose: () => void;
  buildArchivoUrl: (archivoId: string) => string;
  onValidar: (docId: string) => void;
  onDevolver: (docId: string, nota: string) => void;
  validando: boolean;
  devolviendo: boolean;
  errorAccion?: string | null;
  /** revision = entrega del cliente pendiente de validar; devolucion = ya validado y se revierte */
  modo?: 'revision' | 'devolucion';
}

export function RequerimientosDocumentoRevisionModal({
  doc,
  open,
  onClose,
  buildArchivoUrl,
  onValidar,
  onDevolver,
  validando,
  devolviendo,
  errorAccion,
  modo = 'revision',
}: RequerimientosDocumentoRevisionModalProps) {
  const [nota, setNota] = useState('');
  const [mostrarDevolucion, setMostrarDevolucion] = useState(false);

  useEffect(() => {
    if (!open) {
      setNota('');
      setMostrarDevolucion(false);
      return;
    }
    setMostrarDevolucion(modo === 'devolucion');
    setNota('');
  }, [open, doc?.id, modo]);

  if (!doc) return null;

  const procesando = validando || devolviendo;
  const esDevolucion = modo === 'devolucion';
  const archivos = doc.archivos ?? [];

  return (
    <Dialog open={open} onOpenChange={(v) => !v && onClose()}>
      <DialogContent className="max-h-[92vh] max-w-5xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {esDevolucion ? `Devolver: ${doc.nombre}` : `Revisar: ${doc.nombre}`}
          </DialogTitle>
          <DialogDescription>
            {esDevolucion
              ? 'Revise el documento validado. Si detecta un error, devuélvalo al cliente con una nota explicativa.'
              : 'Previsualice el documento entregado por el cliente y valídelo o devuélvalo con una nota.'}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-5 py-2">
          {doc.descripcion && (
            <p className="text-sm text-muted-foreground">{doc.descripcion}</p>
          )}

          {archivos.length > 0 && (
            <DocumentoPdfGaleria
              archivos={archivos}
              buildUrl={buildArchivoUrl}
              title={doc.nombre}
            />
          )}

          {mostrarDevolucion && (
            <div className="rounded-lg border border-amber-200 bg-amber-50/50 p-4 space-y-3">
              <Label htmlFor="nota-requerimientos" className="text-sm font-medium">
                Nota para el cliente
              </Label>
              <textarea
                id="nota-requerimientos"
                className="input-field min-h-[100px] w-full resize-y"
                placeholder="Indique qué debe corregir o volver a subir el cliente…"
                value={nota}
                onChange={(e) => setNota(e.target.value)}
                maxLength={1000}
              />
              <p className="text-xs text-muted-foreground">
                Mínimo 5 caracteres. El cliente recibirá un correo y verá este mensaje en su portal.
              </p>
            </div>
          )}

          {errorAccion && <p className="text-sm text-destructive">{errorAccion}</p>}
        </div>

        <DialogFooter className="flex-col gap-2 sm:flex-row sm:justify-between">
          <div className="flex flex-wrap gap-2">
            {!mostrarDevolucion && !esDevolucion ? (
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
                {!esDevolucion && (
                  <Button
                    type="button"
                    variant="ghost"
                    onClick={() => {
                      setMostrarDevolucion(false);
                      setNota('');
                    }}
                    disabled={procesando}
                  >
                    Cancelar
                  </Button>
                )}
                <Button
                  type="button"
                  variant="destructive"
                  onClick={() => onDevolver(doc.id, nota.trim())}
                  disabled={procesando || nota.trim().length < 5}
                >
                  {devolviendo ? 'Enviando…' : esDevolucion ? 'Revocar validación y devolver' : 'Enviar nota y devolver'}
                </Button>
              </>
            )}
          </div>
          <div className="flex gap-2">
            <Button variant="outline" onClick={onClose} disabled={procesando}>
              Cerrar
            </Button>
            {!esDevolucion && (
              <Button
                onClick={() => onValidar(doc.id)}
                disabled={procesando || mostrarDevolucion}
              >
                {validando ? 'Validando…' : 'Validar documento'}
              </Button>
            )}
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
