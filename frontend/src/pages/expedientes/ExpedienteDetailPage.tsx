import { useQuery } from '@tanstack/react-query';
import { api } from '@/api/client';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { MetricCards } from '@/components/expedientes/MetricCards';
import { ModoHolded } from '@/components/expedientes/ModoHolded';
import { ModoStripe } from '@/components/expedientes/ModoStripe';

interface ExpedienteDetailPageProps {
  expedienteId: string;
}

export function ExpedienteDetailPage({ expedienteId }: ExpedienteDetailPageProps) {
  const { data: expediente } = useQuery({
    queryKey: ['expediente', expedienteId],
    queryFn: () => api.getExpedientes().then((list) => list.find((e) => e.id === expedienteId)),
  });

  return (
    <div className="p-6">
      <div className="mb-6">
        <p className="text-xs font-medium uppercase tracking-wider text-slate-400">
          Expedientes / {expediente?.numero ?? '…'}
        </p>
        <h1 className="mt-1 text-2xl font-semibold text-slate-800">
          {expediente?.titulo ?? 'Cargando…'}
        </h1>
        {expediente && <p className="mt-0.5 text-sm text-slate-500">{expediente.clientName}</p>}
      </div>

      <Tabs defaultValue="facturacion" className="w-full">
        <TabsList className="mb-6 h-10 rounded-lg bg-slate-100 p-1">
          <TabsTrigger value="gestion">Gestión de Fases</TabsTrigger>
          <TabsTrigger value="chat">Chat Interno</TabsTrigger>
          <TabsTrigger value="documentacion">Documentación</TabsTrigger>
          <TabsTrigger value="facturacion">Facturación</TabsTrigger>
        </TabsList>

        <TabsContent value="gestion">
          <StubTab label="Gestión de Fases" />
        </TabsContent>
        <TabsContent value="chat">
          <StubTab label="Chat Interno" />
        </TabsContent>
        <TabsContent value="documentacion">
          <StubTab label="Documentación" />
        </TabsContent>
        <TabsContent value="facturacion">
          <FacturacionTab expedienteId={expedienteId} />
        </TabsContent>
      </Tabs>
    </div>
  );
}

function StubTab({ label }: { label: string }) {
  return (
    <div className="flex items-center justify-center py-16 text-slate-400">
      <p className="text-sm">{label} — próximamente</p>
    </div>
  );
}

function FacturacionTab({ expedienteId }: { expedienteId: string }) {
  const { data: payments } = useQuery({
    queryKey: ['payments', expedienteId],
    queryFn: () => api.getExpedientePayments(expedienteId),
  });

  const { data: invoices } = useQuery({
    queryKey: ['invoices', expedienteId],
    queryFn: () => api.getInvoices(expedienteId),
  });

  const totalCobrado = payments
    ? payments.reduce((sum, p) => sum + parseFloat(p.amount || '0'), 0)
    : 0;

  const totalExpediente = invoices
    ? invoices.reduce((sum, inv) => sum + parseFloat(inv.importe || '0'), 0)
    : 0;

  const pendiente = Math.max(0, totalExpediente - totalCobrado);

  return (
    <div className="space-y-6">
      <MetricCards
        totalExpediente={totalExpediente}
        totalCobrado={totalCobrado}
        pendiente={pendiente}
      />
      <Separator />
      <ModoHolded expedienteId={expedienteId} invoices={invoices ?? []} />
      <Separator />
      <ModoStripe expedienteId={expedienteId} />
    </div>
  );
}
