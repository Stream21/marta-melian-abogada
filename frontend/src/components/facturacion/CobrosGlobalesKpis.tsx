import { type CobrosGlobalesResponse } from '@/api/client';

const fmt = (n: number) =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n);

interface CobrosGlobalesKpisProps {
  kpis: CobrosGlobalesResponse['kpis'];
}

export function CobrosGlobalesKpis({ kpis }: CobrosGlobalesKpisProps) {
  return (
    <div className="grid gap-4 sm:grid-cols-3">
      <div className="panel p-4">
        <p className="section-label">Cobrado (mes)</p>
        <p className="mt-1 text-2xl font-semibold text-foreground">{fmt(kpis.cobradoMes)}</p>
      </div>
      <div className="panel p-4">
        <p className="section-label">Pendiente sync Holded</p>
        <p className="mt-1 text-2xl font-semibold text-amber-700">{kpis.pendienteSyncHolded}</p>
      </div>
      <div className="panel p-4">
        <p className="section-label">Stripe pendientes</p>
        <p className="mt-1 text-2xl font-semibold text-muted-foreground">{kpis.stripePendientes}</p>
      </div>
    </div>
  );
}
