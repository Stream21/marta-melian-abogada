import { type GastosResponse } from '@/api/client';

const fmt = (n: number) =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n);

interface GastosKpisProps {
  kpis: GastosResponse['kpis'];
}

export function GastosKpis({ kpis }: GastosKpisProps) {
  return (
    <div className="grid gap-4 sm:grid-cols-3">
      <div className="panel p-4">
        <p className="section-label">Total (filtro)</p>
        <p className="mt-1 text-2xl font-semibold text-foreground">{fmt(kpis.totalPeriodo)}</p>
      </div>
      <div className="panel p-4">
        <p className="section-label">Nº de gastos</p>
        <p className="mt-1 text-2xl font-semibold text-foreground">{kpis.cantidad}</p>
      </div>
      <div className="panel p-4">
        <p className="section-label">Total mes actual</p>
        <p className="mt-1 text-2xl font-semibold text-muted-foreground">{fmt(kpis.totalMesActual)}</p>
      </div>
    </div>
  );
}
