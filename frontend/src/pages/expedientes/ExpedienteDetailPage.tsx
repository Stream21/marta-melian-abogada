import { useQuery } from '@tanstack/react-query';
import { api } from '@/api/client';
import { ContratacionGestionPanel } from '@/components/expedientes/contratacion/ContratacionGestionPanel';
import { RequerimientosEnConstruccionPanel } from '@/components/expedientes/contratacion/RequerimientosEnConstruccionPanel';
import { EscritoGeneradorPanel } from '@/components/expedientes/EscritoGeneradorPanel';
import { ExpedienteDocumentacionPanel } from '@/components/expedientes/ExpedienteDocumentacionPanel';
import { ExpedienteAuditoriaPanel } from '@/components/expedientes/ExpedienteAuditoriaPanel';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { MetricCards } from '@/components/expedientes/MetricCards';
import { ModoHolded } from '@/components/expedientes/ModoHolded';
import { ModoStripe } from '@/components/expedientes/ModoStripe';

interface ExpedienteDetailPageProps {
  expedienteId: string;
}

const FASE_LABELS: Record<string, string> = {
  contratacion: 'Contratación',
  requerimientos: 'Requerimientos',
  tramitacion: 'Tramitación',
  resolucion: 'Resolución',
};

export function ExpedienteDetailPage({ expedienteId }: ExpedienteDetailPageProps) {
  const { data: expediente } = useQuery({
    queryKey: ['expediente', expedienteId],
    queryFn: () => api.getExpediente(expedienteId),
  });

  const defaultTab = expediente?.faseNegocio === 'contratacion' ? 'gestion' : 'gestion';

  return (
    <div className="p-6">
      <div className="mb-6">
        <p className="section-label">Expedientes / {expediente?.numero ?? '…'}</p>
        <h1 className="mt-1 page-title">{expediente?.titulo ?? 'Cargando…'}</h1>
        {expediente && (
          <div className="mt-2 flex flex-wrap items-center gap-2">
            <p className="page-subtitle">{expediente.clientName}</p>
            {expediente.faseNegocio && (
              <Badge variant="info">
                {FASE_LABELS[expediente.faseNegocio] ?? expediente.faseNegocio}
              </Badge>
            )}
            {expediente.estadoFase && (
              <Badge variant="secondary">{expediente.estadoFase.replace(/_/g, ' ')}</Badge>
            )}
            {expediente.honorariosAcordados != null && expediente.honorariosAcordados > 0 && (
              <span className="text-sm text-muted-foreground">
                {expediente.honorariosAcordados.toLocaleString('es-ES', { minimumFractionDigits: 2 })} €
              </span>
            )}
          </div>
        )}
      </div>

      <Tabs defaultValue={defaultTab} className="w-full">
        <TabsList className="mb-6 h-10 rounded-lg bg-muted p-1">
          <TabsTrigger value="gestion">Gestión de Fases</TabsTrigger>
          <TabsTrigger value="escritos" disabled={expediente?.faseNegocio === 'contratacion'}>
            Escritos
          </TabsTrigger>
          <TabsTrigger value="documentacion">Documentación</TabsTrigger>
          <TabsTrigger value="auditoria">Auditoría</TabsTrigger>
          <TabsTrigger value="facturacion">Facturación</TabsTrigger>
        </TabsList>

        <TabsContent value="gestion">
          {expediente?.faseNegocio === 'contratacion' ? (
            <ContratacionGestionPanel expedienteId={expedienteId} />
          ) : expediente ? (
            <RequerimientosEnConstruccionPanel
              expedienteId={expedienteId}
              numero={expediente.numero}
            />
          ) : (
            <StubTab label="Gestión de Fases" />
          )}
        </TabsContent>
        <TabsContent value="escritos">
          {expediente?.faseNegocio === 'contratacion' ? (
            <FaseEscritosNoDisponible />
          ) : expediente ? (
            <EscritoGeneradorPanel expediente={expediente} />
          ) : (
            <StubTab label="Escritos" />
          )}
        </TabsContent>
        <TabsContent value="documentacion">
          <ExpedienteDocumentacionPanel expedienteId={expedienteId} />
        </TabsContent>
        <TabsContent value="auditoria">
          <ExpedienteAuditoriaPanel expedienteId={expedienteId} />
        </TabsContent>
        <TabsContent value="facturacion">
          <FacturacionTab expedienteId={expedienteId} />
        </TabsContent>
      </Tabs>
    </div>
  );
}

function FaseEscritosNoDisponible() {
  return (
    <div className="panel p-8 text-center">
      <p className="font-medium">Escritos no disponibles en fase de contratación</p>
      <p className="mt-2 text-sm text-muted-foreground max-w-md mx-auto">
        Los escritos adicionales (requerimientos, tramitación, resolución) estarán disponibles a partir de la fase 2.
        En contratación solo se generan los documentos legales firmados por el cliente.
      </p>
    </div>
  );
}

function StubTab({ label }: { label: string }) {
  return (
    <div className="flex items-center justify-center py-16 text-muted-foreground">
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

  return (
    <div className="space-y-6">
      <MetricCards
        totalExpediente={totalExpediente}
        totalCobrado={totalCobrado}
        pendiente={Math.max(0, totalExpediente - totalCobrado)}
      />
      <Separator />
      <div className="grid gap-6 lg:grid-cols-2">
        <ModoHolded expedienteId={expedienteId} invoices={invoices ?? []} />
        <ModoStripe expedienteId={expedienteId} />
      </div>
    </div>
  );
}
