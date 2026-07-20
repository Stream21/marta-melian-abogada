import { useCallback, useEffect, useRef, useState } from 'react';
import { Eraser, PenLine, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  canvasToTransparentPngFile,
  clearSignatureCanvas,
  isCanvasBlank,
  setupSignatureCanvas,
  type SignaturePoint,
} from '@/lib/signature-pad';
import { cn } from '@/lib/utils';

const CANVAS_WIDTH = 520;
const CANVAS_HEIGHT = 180;

export interface SignaturePadProps {
  onSave: (file: File) => void | Promise<void>;
  savedImageUrl?: string | null;
  isSaving?: boolean;
  disabled?: boolean;
  title?: string;
  description?: string;
  filename?: string;
  className?: string;
}

function getPoint(canvas: HTMLCanvasElement, event: PointerEvent): SignaturePoint {
  const rect = canvas.getBoundingClientRect();
  return {
    x: event.clientX - rect.left,
    y: event.clientY - rect.top,
  };
}

export function SignaturePad({
  onSave,
  savedImageUrl = null,
  isSaving = false,
  disabled = false,
  title = 'Firma manuscrita',
  filename = 'firma.png',
  className,
}: SignaturePadProps) {
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const drawingRef = useRef(false);
  const lastPointRef = useRef<SignaturePoint | null>(null);
  const [isRedrawing, setIsRedrawing] = useState(false);
  const [localError, setLocalError] = useState<string | null>(null);
  const [hasStroke, setHasStroke] = useState(false);

  const showPad = !savedImageUrl || isRedrawing;

  const resetCanvas = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    clearSignatureCanvas(canvas, CANVAS_WIDTH, CANVAS_HEIGHT);
    setHasStroke(false);
    setLocalError(null);
  }, []);

  useEffect(() => {
    if (!showPad) return;
    resetCanvas();
  }, [showPad, resetCanvas]);

  const handlePointerDown = (event: React.PointerEvent<HTMLCanvasElement>) => {
    if (disabled || isSaving) return;

    const canvas = canvasRef.current;
    const context = canvas?.getContext('2d');
    if (!canvas || !context) return;

    event.currentTarget.setPointerCapture(event.pointerId);
    drawingRef.current = true;
    lastPointRef.current = getPoint(canvas, event.nativeEvent);
    setLocalError(null);
  };

  const handlePointerMove = (event: React.PointerEvent<HTMLCanvasElement>) => {
    if (!drawingRef.current || disabled || isSaving) return;

    const canvas = canvasRef.current;
    const context = canvas?.getContext('2d');
    if (!canvas || !context || !lastPointRef.current) return;

    const point = getPoint(canvas, event.nativeEvent);
    context.beginPath();
    context.moveTo(lastPointRef.current.x, lastPointRef.current.y);
    context.lineTo(point.x, point.y);
    context.stroke();
    lastPointRef.current = point;
    setHasStroke(true);
  };

  const stopDrawing = (event: React.PointerEvent<HTMLCanvasElement>) => {
    if (!drawingRef.current) return;
    drawingRef.current = false;
    lastPointRef.current = null;
    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
      event.currentTarget.releasePointerCapture(event.pointerId);
    }
  };

  const handleSave = async () => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    if (!hasStroke || isCanvasBlank(canvas)) {
      setLocalError('Dibuje su firma antes de guardar.');
      return;
    }

    try {
      setLocalError(null);
      const file = await canvasToTransparentPngFile(canvas, filename);
      await onSave(file);
      setIsRedrawing(false);
      resetCanvas();
    } catch (error) {
      setLocalError(error instanceof Error ? error.message : 'No se pudo guardar la firma.');
    }
  };

  return (
    <div className={cn('space-y-', className)}>
      <div>
        <p className="section-label">{title}</p>
      </div>

      {!showPad && savedImageUrl && (
        <div className="space-y-3">
          <div className="signature-transparency-grid flex min-h-[120px] items-center justify-center rounded-lg border border-border p-4">
            <img
              src={savedImageUrl}
              alt="Firma guardada"
              className="max-h-24 max-w-full object-contain"
            />
          </div>
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={disabled || isSaving}
            onClick={() => setIsRedrawing(true)}
          >
            <PenLine className="h-4 w-4" />Firmar de nuevo
          </Button>
        </div>
      )}

      {showPad && (
        <>
          <div className="signature-transparency-grid overflow-hidden rounded-lg border border-border">
            <canvas
              ref={(node) => {
                canvasRef.current = node;
                if (node && !node.dataset.ready) {
                  setupSignatureCanvas(node, CANVAS_WIDTH, CANVAS_HEIGHT);
                  node.dataset.ready = 'true';
                }
              }}
              width={CANVAS_WIDTH}
              height={CANVAS_HEIGHT}
              className="block w-full touch-none cursor-crosshair"
              onPointerDown={handlePointerDown}
              onPointerMove={handlePointerMove}
              onPointerUp={stopDrawing}
              onPointerLeave={stopDrawing}
              onPointerCancel={stopDrawing}
              aria-label="Área para dibujar la firma"
            />
          </div>

          <p className="text-xs text-muted-foreground">
            El fondo cuadriculado solo es una guía visual. La firma guardada no incluye fondo.
          </p>

          {localError && (
            <p className="text-sm text-destructive" role="alert">
              {localError}
            </p>
          )}

          <div className="flex flex-wrap gap-2">
            <Button
              type="button"
              size="sm"
              disabled={disabled || isSaving}
              onClick={() => void handleSave()}
            >
              <Save className="h-4 w-4" />
              {isSaving ? 'Guardando…' : 'Guardar firma'}
            </Button>
            <Button
              type="button"
              variant="outline"
              size="sm"
              disabled={disabled || isSaving}
              onClick={resetCanvas}
            >
              <Eraser className="h-4 w-4" />
              Limpiar
            </Button>
            {savedImageUrl && (
              <Button
                type="button"
                variant="ghost"
                size="sm"
                disabled={disabled || isSaving}
                onClick={() => {
                  setIsRedrawing(false);
                  resetCanvas();
                }}
              >
                Cancelar
              </Button>
            )}
          </div>
        </>
      )}
    </div>
  );
}
