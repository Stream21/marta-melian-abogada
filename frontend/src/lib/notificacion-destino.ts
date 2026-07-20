import type { NotificacionResponse } from '@/api/client';

export interface ExpedienteNotificacionSearch {
  tab?: string;
  hito?: string;
  paso?: string;
  documento?: string;
  revision?: string;
}

export function buildExpedienteNotificacionSearch(n: NotificacionResponse): ExpedienteNotificacionSearch {
  const search: ExpedienteNotificacionSearch = {
    tab: n.destinoTab,
    hito: n.destinoHitoId ?? n.id,
  };

  if (n.destinoPaso) {
    search.paso = n.destinoPaso;
  }

  if (n.destinoReferenciaId) {
    search.documento = n.destinoReferenciaId;
  }

  if (n.abrirRevision) {
    search.revision = '1';
  }

  return search;
}
