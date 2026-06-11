import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, fetchAuthenticatedAsset, getDespachoAssetPath } from '@/api/client';
import { EscritoCanvas } from '@/components/hoja-encargo/HojaEncargoCanvas';
import { EscritoDesignerToolbar } from '@/components/hoja-encargo/EscritoDesignerToolbar';
import { EscritoPlantillaStatus } from '@/components/hoja-encargo/EscritoPlantillaStatus';
import { EscritoPdfPreview } from '@/components/hoja-encargo/EscritoPdfPreview';
import { VariablesSidebar } from '@/components/hoja-encargo/VariablesSidebar';
import {
  locateBlock,
  removeBlockFromTree,
  updateBlockInTree,
} from '@/lib/escrito-block-tree';
import {
  createColumnsBlock,
  insertVariableAtCursor,
  normalizeLegacyBlocks,
  type BloqueEscrito,
  type RootBlockAddType,
  type TipoEscrito,
} from '@/lib/hoja-encargo-variables';

interface EscritoDesignerProps {
  tramiteId: string;
  tipo: TipoEscrito;
}

function isTextBlock(bloque: BloqueEscrito | undefined): bloque is Extract<
  BloqueEscrito,
  { type: 'text' | 'section' }
> {
  return bloque?.type === 'text' || bloque?.type === 'section';
}

/** Estos tres documentos del trámite siempre llevan membrete (cabecera y pie). */
const INCLUIR_MEMBRETE = true;

export function EscritoDesigner({ tramiteId, tipo }: EscritoDesignerProps) {
  const queryClient = useQueryClient();
  const [bloques, setBloques] = useState<BloqueEscrito[]>([]);
  const [selectedBlockId, setSelectedBlockId] = useState<string | null>(null);
  const [preview, setPreview] = useState(false);
  const [logoPreview, setLogoPreview] = useState<string | null>(null);
  const [selloPreview, setSelloPreview] = useState<string | null>(null);
  const [initialized, setInitialized] = useState(false);
  const [savedBloquesKey, setSavedBloquesKey] = useState<string | null>(null);

  const { data: tramite } = useQuery({
    queryKey: ['tramite', tramiteId],
    queryFn: () => api.getTramite(tramiteId),
  });

  const { data: despacho } = useQuery({
    queryKey: ['despacho-config'],
    queryFn: () => api.getDespachoConfig(),
  });

  const { data: variables = [] } = useQuery({
    queryKey: ['escrito-variables'],
    queryFn: () => api.getEscritoVariables(),
  });

  const { data: plantilla, isLoading } = useQuery({
    queryKey: ['escrito-plantilla', tramiteId, tipo],
    queryFn: () => api.getEscritoPlantilla(tramiteId, tipo),
  });

  useEffect(() => {
    setInitialized(false);
    setBloques([]);
    setSelectedBlockId(null);
    setPreview(false);
    setSavedBloquesKey(null);
  }, [tramiteId, tipo]);

  useEffect(() => {
    if (!plantilla || initialized) return;
    const normalized = normalizeLegacyBlocks(plantilla.bloques as BloqueEscrito[]);
    const key = JSON.stringify(normalized);
    setBloques(normalized);
    setSavedBloquesKey(key);
    setSelectedBlockId(normalized[0]?.id ?? null);
    setInitialized(true);
  }, [plantilla, initialized]);

  useEffect(() => {
    let active = true;
    let logoObjectUrl: string | null = null;
    let selloObjectUrl: string | null = null;

    async function loadAssets() {
      if (despacho?.logoUrl) {
        logoObjectUrl = await fetchAuthenticatedAsset(
          getDespachoAssetPath('logo'),
          despacho.updatedAt ?? undefined,
        );
        if (active) setLogoPreview(logoObjectUrl);
      } else if (active) {
        setLogoPreview(null);
      }

      if (despacho?.selloUrl) {
        selloObjectUrl = await fetchAuthenticatedAsset(
          getDespachoAssetPath('sello'),
          despacho.updatedAt ?? undefined,
        );
        if (active) setSelloPreview(selloObjectUrl);
      } else if (active) {
        setSelloPreview(null);
      }
    }

    void loadAssets();

    return () => {
      active = false;
      if (logoObjectUrl) URL.revokeObjectURL(logoObjectUrl);
      if (selloObjectUrl) URL.revokeObjectURL(selloObjectUrl);
    };
  }, [despacho?.logoUrl, despacho?.selloUrl, despacho?.updatedAt]);

  const saveMutation = useMutation({
    mutationFn: () => api.putEscritoPlantilla(tramiteId, tipo, bloques),
    onSuccess: () => {
      setSavedBloquesKey(JSON.stringify(bloques));
      void queryClient.invalidateQueries({ queryKey: ['escrito-plantilla', tramiteId, tipo] });
      void queryClient.invalidateQueries({ queryKey: ['hoja-encargo', tramiteId] });
    },
  });

  const selectedLocation = useMemo(
    () => (selectedBlockId ? locateBlock(bloques, selectedBlockId) : null),
    [bloques, selectedBlockId],
  );

  const selectedIndex = selectedLocation?.parent ? -1 : (selectedLocation?.rootIndex ?? -1);
  const selectedBlock = selectedLocation?.block;

  const updateBlock = (updated: BloqueEscrito) => {
    setBloques((current) => updateBlockInTree(current, updated));
  };

  const replaceBlocks = (next: BloqueEscrito[]) => {
    setBloques(next);
  };

  const handleAddBlock = (type: RootBlockAddType) => {
    const newBlock =
      type === 'columns_1' ? createColumnsBlock(1) : createColumnsBlock(2);
    setBloques((current) => {
      if (selectedIndex >= 0) {
        const next = [...current];
        next.splice(selectedIndex + 1, 0, newBlock);
        return next;
      }
      return [...current, newBlock];
    });
    setSelectedBlockId(newBlock.id);
  };

  const handleRemove = () => {
    if (!selectedBlockId || !selectedLocation) return;
    if (!selectedLocation.parent && bloques.length <= 1) return;

    const next = removeBlockFromTree(bloques, selectedBlockId);
    const fallbackLocation = selectedLocation.parent
      ? { id: selectedLocation.parent.id }
      : next[selectedLocation.rootIndex - 1] ?? next[selectedLocation.rootIndex];
    setBloques(next);
    setSelectedBlockId(fallbackLocation?.id ?? null);
  };

  const handleInsertVariable = (key: string) => {
    if (!isTextBlock(selectedBlock)) return;

    const textarea = document.querySelector<HTMLTextAreaElement>(
      `textarea[data-block-id="${selectedBlock.id}"]`,
    );
    const selectionStart = textarea?.selectionStart ?? selectedBlock.content.length;
    const selectionEnd = textarea?.selectionEnd ?? selectionStart;
    const { content, cursor } = insertVariableAtCursor(
      selectedBlock.content,
      key,
      selectionStart,
      selectionEnd,
    );

    updateBlock({ ...selectedBlock, content });

    requestAnimationFrame(() => {
      const nextTextarea = document.querySelector<HTMLTextAreaElement>(
        `textarea[data-block-id="${selectedBlock.id}"]`,
      );
      if (nextTextarea) {
        nextTextarea.focus();
        nextTextarea.setSelectionRange(cursor, cursor);
      }
    });
  };

  const saveError = saveMutation.error instanceof Error ? saveMutation.error.message : null;
  const bloquesKey = useMemo(() => JSON.stringify(bloques), [bloques]);
  const hasUnsavedChanges = savedBloquesKey !== null && bloquesKey !== savedBloquesKey;

  if (isLoading && !initialized) {
    return (
      <div className="flex h-full items-center justify-center text-sm text-muted-foreground">
        Cargando diseñador…
      </div>
    );
  }

  return (
    <div className="flex h-full min-h-0 flex-col gap-2">
      {plantilla && (
        <div className="flex shrink-0 justify-end px-1">
          <EscritoPlantillaStatus
            esDefault={plantilla.esDefault}
            esPlantillaGlobal={plantilla.esPlantillaGlobal}
            hasUnsavedChanges={hasUnsavedChanges}
          />
        </div>
      )}

      <EscritoDesignerToolbar
        preview={preview}
        onTogglePreview={() => setPreview((value) => !value)}
        onSave={() => saveMutation.mutate()}
        savePending={saveMutation.isPending}
        showBlockToolbar={!preview}
        onAddBlock={handleAddBlock}
        onRemove={handleRemove}
        canRemove={
          selectedLocation !== null &&
          (selectedLocation.parent !== null || bloques.length > 1)
        }
      />

      {saveError && (
        <p
          className="shrink-0 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
          role="alert"
        >
          {saveError}
        </p>
      )}

      <div className="grid min-h-0 flex-1 grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_280px]">
        <div className="min-h-0 overflow-y-auto overscroll-contain rounded-lg bg-muted/20 p-4">
          {preview ? (
            <EscritoPdfPreview
              tramiteId={tramiteId}
              tipo={tipo}
              bloques={bloques}
              incluirMembrete={INCLUIR_MEMBRETE}
            />
          ) : (
            <EscritoCanvas
              bloques={bloques}
              selectedBlockId={selectedBlockId}
              preview={false}
              despacho={despacho}
              tramite={tramite}
              logoUrl={logoPreview}
              selloUrl={selloPreview}
              onSelectBlock={setSelectedBlockId}
              onChangeBlock={updateBlock}
              onChangeBlocks={replaceBlocks}
            />
          )}
        </div>

        {!preview && (
          <div className="hidden h-full min-h-0 xl:block">
            <VariablesSidebar
              categories={variables}
              onInsertVariable={handleInsertVariable}
              disabled={!isTextBlock(selectedBlock) || saveMutation.isPending}
            />
          </div>
        )}
      </div>
    </div>
  );
}

export function HojaEncargoDesigner({ tramiteId }: { tramiteId: string }) {
  return <EscritoDesigner tramiteId={tramiteId} tipo="hoja_encargo" />;
}
