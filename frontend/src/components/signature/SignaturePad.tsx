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

const FALLBACK_WIDTH = 520;
const FALLBACK_HEIGHT = 180;

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

function pointFromEvent(canvas: HTMLCanvasElement, event: PointerEvent): SignaturePoint {
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
  const contextRef = useRef<CanvasRenderingContext2D | null>(null);
  const drawingRef = useRef(false);
  const lastPointRef = useRef<SignaturePoint | null>(null);
  const hasStrokeRef = useRef(false);
  const layoutRef = useRef({ w: FALLBACK_WIDTH, h: FALLBACK_HEIGHT });

  const [isRedrawing, setIsRedrawing] = useState(false);
  const [localError, setLocalError] = useState<string | null>(null);
  const [canSave, setCanSave] = useState(false);

  const showPad = !savedImageUrl || isRedrawing;
  const disabledRef = useRef(disabled);
  const isSavingRef = useRef(isSaving);
  disabledRef.current = disabled;
  isSavingRef.current = isSaving;

  const setupFromLayout = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const rect = canvas.getBoundingClientRect();
    const w = rect.width > 0 ? rect.width : FALLBACK_WIDTH;
    const h = rect.height > 0 ? rect.height : FALLBACK_HEIGHT;
    layoutRef.current = { w, h };
    contextRef.current = setupSignatureCanvas(canvas, w, h);
  }, []);

  const resetStrokeState = useCallback((clearBitmap: boolean) => {
    drawingRef.current = false;
    lastPointRef.current = null;
    hasStrokeRef.current = false;
    setCanSave(false);
    setLocalError(null);

    if (!clearBitmap) return;
    const canvas = canvasRef.current;
    if (!canvas) return;
    const { w, h } = layoutRef.current;
    contextRef.current = clearSignatureCanvas(canvas, w, h);
  }, []);

  // Montaje / volver a mostrar el pad: tamaño real tras layout.
  useEffect(() => {
    if (!showPad) return;

    let cancelled = false;
    const frame = requestAnimationFrame(() => {
      if (cancelled) return;
      setupFromLayout();
      drawingRef.current = false;
      lastPointRef.current = null;
      hasStrokeRef.current = false;
      setCanSave(false);
    });

    return () => {
      cancelled = true;
      cancelAnimationFrame(frame);
    };
  }, [showPad, setupFromLayout]);

  // Listeners nativos: en iOS React + setPointerCapture rompen el multitrazo.
  useEffect(() => {
    if (!showPad) return;
    const canvas = canvasRef.current;
    if (!canvas) return;

    const onPointerMove = (event: PointerEvent) => {
      if (!drawingRef.current || disabledRef.current || isSavingRef.current) return;

      const context = contextRef.current;
      const last = lastPointRef.current;
      if (!context || !last) return;

      const point = pointFromEvent(canvas, event);
      context.beginPath();
      context.moveTo(last.x, last.y);
      context.lineTo(point.x, point.y);
      context.stroke();
      lastPointRef.current = point;
      hasStrokeRef.current = true;
    };

    const endStroke = () => {
      if (!drawingRef.current) return;
      drawingRef.current = false;
      lastPointRef.current = null;
      window.removeEventListener('pointermove', onPointerMove);
      window.removeEventListener('pointerup', endStroke);
      window.removeEventListener('pointercancel', endStroke);
      // setState solo al soltar: un re-render a mitad de gesto rompe el touch en iOS.
      if (hasStrokeRef.current) {
        setCanSave(true);
      }
    };

    const onPointerDown = (event: PointerEvent) => {
      if (disabledRef.current || isSavingRef.current) return;
      if (event.pointerType === 'mouse' && event.button !== 0) return;

      const context = contextRef.current;
      if (!context) return;

      drawingRef.current = true;
      lastPointRef.current = pointFromEvent(canvas, event);
      window.addEventListener('pointermove', onPointerMove);
      window.addEventListener('pointerup', endStroke);
      window.addEventListener('pointercancel', endStroke);
    };

    canvas.addEventListener('pointerdown', onPointerDown);

    return () => {
      canvas.removeEventListener('pointerdown', onPointerDown);
      window.removeEventListener('pointermove', onPointerMove);
      window.removeEventListener('pointerup', endStroke);
      window.removeEventListener('pointercancel', endStroke);
    };
  }, [showPad]);

  const handleSave = async () => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    drawingRef.current = false;
    lastPointRef.current = null;

    if (!hasStrokeRef.current || isCanvasBlank(canvas)) {
      setLocalError('Dibuje su firma antes de guardar.');
      return;
    }

    try {
      setLocalError(null);
      const file = await canvasToTransparentPngFile(canvas, filename);
      await onSave(file);
      setIsRedrawing(false);
      resetStrokeState(true);
    } catch (error) {
      setLocalError(error instanceof Error ? error.message : 'No se pudo guardar la firma.');
    }
  };

  return (
    <div className={cn('space-y-3', className)}>
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
            <PenLine className="h-4 w-4" />
            Firmar de nuevo
          </Button>
        </div>
      )}

      {showPad && (
        <>
          <div className="signature-transparency-grid overflow-hidden rounded-lg border border-border">
            <canvas
              ref={canvasRef}
              className="block h-[180px] w-full max-w-[520px] touch-none cursor-crosshair select-none"
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
              disabled={disabled || isSaving || !canSave}
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
              onClick={() => resetStrokeState(true)}
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
                  resetStrokeState(true);
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
