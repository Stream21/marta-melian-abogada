import { useState } from 'react';
import type { DespachoConfigResponse, TramiteResponse } from '@/api/client';
import { BloqueRenderer } from '@/components/hoja-encargo/BloqueRenderer';
import { EscritoBlockRow } from '@/components/hoja-encargo/EscritoBlockRow';
import { MembretePreview } from '@/components/hoja-encargo/MembretePreview';
import type { BloqueEscrito, VariablePreviewContext } from '@/lib/hoja-encargo-variables';
import {
  getRowInsertPosition,
  parseRowDragData,
  reorderRootBlocks,
  type RowInsertPosition,
} from '@/lib/escrito-row-drag';
import { cn } from '@/lib/utils';

interface EscritoCanvasProps {
  bloques: BloqueEscrito[];
  selectedBlockId: string | null;
  preview: boolean;
  despacho?: DespachoConfigResponse;
  tramite?: TramiteResponse;
  logoUrl?: string | null;
  selloUrl?: string | null;
  onSelectBlock: (id: string) => void;
  onChangeBlock: (bloque: BloqueEscrito) => void;
  onChangeBlocks: (bloques: BloqueEscrito[]) => void;
}

/** @deprecated Use EscritoCanvas */
export type HojaEncargoCanvasProps = EscritoCanvasProps;

export function EscritoCanvas({
  bloques,
  selectedBlockId,
  preview,
  despacho,
  tramite,
  logoUrl,
  selloUrl,
  onSelectBlock,
  onChangeBlock,
  onChangeBlocks,
}: EscritoCanvasProps) {
  const [draggingId, setDraggingId] = useState<string | null>(null);
  const [dropIndicator, setDropIndicator] = useState<{
    targetId: string;
    position: RowInsertPosition;
  } | null>(null);

  const previewContext: VariablePreviewContext = {
    despacho: despacho
      ? {
          nombreFirma: despacho.nombreFirma,
          nombreLetrada: despacho.nombreLetrada,
          numColegiado: despacho.numColegiado,
          direccion: despacho.direccion,
          ciudad: despacho.ciudad,
          subtituloProfesional: despacho.subtituloProfesional,
          telefono: despacho.telefono,
          email: despacho.email,
          web: despacho.web,
          nif: despacho.nif,
          colegioAbogados: despacho.colegioAbogados,
          iban: despacho.iban,
          entidadBancaria: despacho.entidadBancaria,
          titularCuenta: despacho.titularCuenta,
        }
      : undefined,
    tramite: tramite
      ? {
          honorarios: tramite.honorarios,
          nombre: tramite.nombre,
        }
      : undefined,
  };

  const clearDragState = () => {
    setDraggingId(null);
    setDropIndicator(null);
  };

  const handleDragOverRow = (targetId: string, position: RowInsertPosition) => {
    if (!draggingId || draggingId === targetId) {
      setDropIndicator(null);
      return;
    }
    setDropIndicator({ targetId, position });
  };

  const handleDrop = (targetId: string, event: React.DragEvent) => {
    event.preventDefault();
    const draggedId = parseRowDragData(event.dataTransfer) ?? draggingId;
    if (!draggedId || draggedId === targetId) {
      clearDragState();
      return;
    }
    const position = getRowInsertPosition(event, event.currentTarget as HTMLElement);
    onChangeBlocks(reorderRootBlocks(bloques, draggedId, targetId, position));
    clearDragState();
  };

  return (
    <div className="mx-auto w-full max-w-[720px]">
      {!preview && (
        <p className="mb-2 text-center text-xs text-muted-foreground">
          Arrastre las filas por el asa lateral para reordenarlas
        </p>
      )}
      <div
        className={cn(
          'relative min-h-[840px] bg-card shadow-lg ring-1 ring-border',
          preview ? 'escrito-document flex flex-col overflow-hidden py-10' : 'p-8',
        )}
      >
        <MembretePreview
          despacho={despacho}
          previewContext={previewContext}
          position="top"
          logoUrl={logoUrl}
          documentPreview={preview}
        />
        <div className={cn('relative z-[1] space-y-2', preview && 'min-h-0 flex-1 py-2')}>
          {bloques.map((bloque) => {
            const blockContent = (
              <BloqueRenderer
                bloque={bloque}
                selected={selectedBlockId === bloque.id}
                preview={preview}
                despacho={despacho}
                previewContext={previewContext}
                logoUrl={logoUrl}
                selloUrl={selloUrl}
                allBloques={bloques}
                selectedBlockId={selectedBlockId}
                onSelect={() => onSelectBlock(bloque.id)}
                onSelectChild={onSelectBlock}
                onChange={onChangeBlock}
                onChangeTree={onChangeBlocks}
              />
            );

            if (preview) {
              return <div key={bloque.id}>{blockContent}</div>;
            }

            return (
              <EscritoBlockRow
                key={bloque.id}
                blockId={bloque.id}
                selected={selectedBlockId === bloque.id}
                dropPosition={
                  dropIndicator?.targetId === bloque.id ? dropIndicator.position : null
                }
                dragging={draggingId === bloque.id}
                onDragStart={setDraggingId}
                onDragEnd={clearDragState}
                onDragOverRow={handleDragOverRow}
                onDrop={handleDrop}
              >
                {blockContent}
              </EscritoBlockRow>
            );
          })}
        </div>
        <MembretePreview
          despacho={despacho}
          previewContext={previewContext}
          position="bottom"
          logoUrl={logoUrl}
          documentPreview={preview}
        />
      </div>
    </div>
  );
}

export const HojaEncargoCanvas = EscritoCanvas;
