import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TrendingUp, CheckCircle2, Clock } from 'lucide-react';

const fmt = (n: number) =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n);

interface MetricCardsProps {
  totalExpediente: number;
  totalCobrado: number;
  pendiente: number;
}

export function MetricCards({ totalExpediente, totalCobrado, pendiente }: MetricCardsProps) {
  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <Card className="border-blue-100 bg-blue-50/60">
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium text-blue-700">Total Expediente</CardTitle>
          <TrendingUp className="h-4 w-4 text-blue-500" />
        </CardHeader>
        <CardContent>
          <p className="text-2xl font-bold text-blue-800">{fmt(totalExpediente)}</p>
          <p className="mt-1 text-xs text-blue-600">Importe total del caso</p>
        </CardContent>
      </Card>

      <Card className="border-emerald-100 bg-emerald-50/60">
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium text-emerald-700">Total Cobrado</CardTitle>
          <CheckCircle2 className="h-4 w-4 text-emerald-500" />
        </CardHeader>
        <CardContent>
          <p className="text-2xl font-bold text-emerald-800">{fmt(totalCobrado)}</p>
          <p className="mt-1 text-xs text-emerald-600">Pagos recibidos</p>
        </CardContent>
      </Card>

      <Card className="border-amber-100 bg-amber-50/60">
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle className="text-sm font-medium text-amber-700">Pendiente de Cobro</CardTitle>
          <Clock className="h-4 w-4 text-amber-500" />
        </CardHeader>
        <CardContent>
          <p className="text-2xl font-bold text-amber-800">{fmt(pendiente)}</p>
          <p className="mt-1 text-xs text-amber-600">Por cobrar</p>
        </CardContent>
      </Card>
    </div>
  );
}
