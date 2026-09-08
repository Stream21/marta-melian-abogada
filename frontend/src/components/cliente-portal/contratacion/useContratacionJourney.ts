import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { AccesoExpedienteResponse, AccesoPasoResponse } from '@/api/client';
import {
  buildMicroItems,
  canConfirmPaso,
  itemLabelForView,
  nextPendingItem,
  viewFromItem,
  type ContratacionView,
} from './types';

interface UseContratacionJourneyArgs {
  data: AccesoExpedienteResponse;
  pasoActivo: AccesoPasoResponse | null;
  esperandoAbogado: boolean;
}

function currentItemId(view: ContratacionView): string | null {
  switch (view.type) {
    case 'identidad':
      return 'identidad';
    case 'doc':
      return `doc:${view.docId}`;
    case 'otp':
      return 'otp';
    case 'firma':
      return `firma:${view.tipo}`;
    case 'pago':
      return 'pago';
    default:
      return null;
  }
}

function resolveDefaultView(
  esperandoAbogado: boolean,
  pasoActivo: AccesoPasoResponse | null,
  items: ReturnType<typeof buildMicroItems>,
): ContratacionView {
  if (esperandoAbogado) return { type: 'waiting' };
  if (!pasoActivo) return { type: 'resumen_final' };
  const first = nextPendingItem(items);
  return first ? viewFromItem(first) : { type: 'hub' };
}

export function useContratacionJourney({
  data,
  pasoActivo,
  esperandoAbogado,
}: UseContratacionJourneyArgs) {
  const items = useMemo(
    () => buildMicroItems(data, pasoActivo),
    [data, pasoActivo],
  );

  const lastPasoRef = useRef<string | null | undefined>(undefined);

  const [view, setView] = useState<ContratacionView>(() =>
    resolveDefaultView(esperandoAbogado, pasoActivo, items),
  );

  useEffect(() => {
    if (esperandoAbogado) {
      setView({ type: 'waiting' });
      lastPasoRef.current = pasoActivo?.paso ?? null;
      return;
    }

    if (!pasoActivo) {
      setView({ type: 'resumen_final' });
      lastPasoRef.current = null;
      return;
    }

    const pasoKey = pasoActivo.paso;
    if (lastPasoRef.current === undefined || pasoKey !== lastPasoRef.current) {
      lastPasoRef.current = pasoKey;
      setView(resolveDefaultView(false, pasoActivo, items));
    }
  }, [esperandoAbogado, pasoActivo, items]);

  useEffect(() => {
    if (view.type !== 'hub') return;
    const pending = nextPendingItem(items);
    if (pending) setView(viewFromItem(pending));
  }, [items, view.type]);

  const irAlSiguiente = useCallback(
    (afterId?: string | null) => {
      const next = nextPendingItem(items, afterId ?? currentItemId(view));
      if (next) {
        setView(viewFromItem(next));
        return;
      }
      setView({ type: 'hub' });
    },
    [items, view],
  );

  const isFocus = view.type !== 'hub' && view.type !== 'resumen_final' && view.type !== 'waiting';
  const activeId = currentItemId(view);
  const focusIndex = Math.max(
    0,
    items.findIndex((i) => i.id === activeId),
  );
  const focusTotal = items.length;

  /** En firmas el contador es solo entre documentos a firmar (p. ej. 2/3), sin el OTP. */
  const firmaItems = useMemo(() => items.filter((i) => i.kind === 'firma'), [items]);
  const chromeIndex =
    view.type === 'firma'
      ? Math.max(
          0,
          firmaItems.findIndex((i) => i.id === `firma:${view.tipo}`),
        )
      : focusIndex;
  const chromeTotal = view.type === 'firma' ? firmaItems.length : focusTotal;

  const title = itemLabelForView(view, items);
  const puedeConfirmar = pasoActivo ? canConfirmPaso(data, pasoActivo) : false;

  return {
    view,
    items,
    isFocus,
    focusIndex,
    focusTotal,
    chromeIndex,
    chromeTotal,
    title,
    puedeConfirmar,
    irAlSiguiente,
  };
}
