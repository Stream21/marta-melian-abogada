import { useEffect, useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/api/client';
import type { ExpedienteNotificacionSearch } from '@/lib/notificacion-destino';
import { ContratacionGestionPanel } from '@/components/expedientes/contratacion/ContratacionGestionPanel';
import { RequerimientosGestionPanel } from '@/components/expedientes/requerimientos/RequerimientosGestionPanel';
import { TramitacionEnConstruccionPanel } from '@/components/expedientes/tramitacion/TramitacionEnConstruccionPanel';
import { ExpedienteEscritosPanel } from '@/components/expedientes/escritos/ExpedienteEscritosPanel';
import { ExpedienteFacturacionPanel } from '@/components/expedientes/ExpedienteFacturacionPanel';
import { ExpedienteDocumentacionPanel } from '@/components/expedientes/ExpedienteDocumentacionPanel';
import { ExpedienteAuditoriaPanel } from '@/components/expedientes/ExpedienteAuditoriaPanel';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { ExpedienteGestionToolbarActions } from '@/components/expedientes/ExpedienteGestionToolbarActions';
import { consumirNotificacionAlta } from '@/lib/email-notificacion';
import { cn } from '@/lib/utils';

interface ExpedienteDetailPageProps {
  expedienteId: string;
  notificacionSearch?: ExpedienteNotificacionSearch;
}

const FASE_LABELS: Record<string, string> = {
  contratacion: 'Contratación',
  requerimientos: 'Requerimientos',
  tramitacion: 'Tramitación',
  resolucion: 'Resolución',
};

export function ExpedienteDetailPage({ expedienteId, notificacionSearch }: ExpedienteDetailPageProps) {
  const navigate = useNavigate();
  const [notificacionAlta, setNotificacionAlta] = useState<{ mensaje: string; esError: boolean } | null>(
    null,
  );

  useEffect(() => {
    setNotificacionAlta(consumirNotificacionAlta());
  }, []);

  const { data: expediente } = useQuery({
    queryKey: ['expediente', expedienteId],
    queryFn: () => api.getExpediente(expedienteId),
  });

  const tabFromNotificacion = notificacionSearch?.tab;
  const [activeTab, setActiveTab] = useState(tabFromNotificacion ?? 'gestion');

  useEffect(() => {
    if (tabFromNotificacion) {
      setActiveTab(tabFromNotificacion);
    }
  }, [tabFromNotificacion]);

  const limpiarNotificacionSearch = () => {
    if (
      !notificacionSearch?.tab &&
      !notificacionSearch?.hito &&
      !notificacionSearch?.paso &&
      !notificacionSearch?.documento
    ) {
      return;
    }
    void navigate({
      to: '/expedientes/$expedienteId',
      params: { expedienteId },
      search: {},
      replace: true,
    });
  };

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

      {notificacionAlta && (
        <div
          className={cn(
            'mb-6 rounded-lg border p-4 text-sm',
            notificacionAlta.esError
              ? 'border-destructive/30 bg-destructive/5 text-destructive'
              : 'border-emerald-200 bg-emerald-50 text-emerald-800',
          )}
          role="status"
        >
          {notificacionAlta.mensaje}
        </div>
      )}

      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
          <TabsList className="h-10 rounded-lg bg-muted p-1">
            <TabsTrigger value="gestion">Gestión de Fases</TabsTrigger>
            <TabsTrigger value="escritos" disabled={expediente?.faseNegocio === 'contratacion'}>
              Escritos
            </TabsTrigger>
            <TabsTrigger value="documentacion">Documentación</TabsTrigger>
            <TabsTrigger value="auditoria">Auditoría</TabsTrigger>
            <TabsTrigger value="facturacion">Facturación</TabsTrigger>
          </TabsList>
          {activeTab === 'gestion' && expediente?.faseNegocio && (
            <ExpedienteGestionToolbarActions
              expedienteId={expedienteId}
              faseNegocio={expediente.faseNegocio}
            />
          )}
        </div>

        <TabsContent value="gestion">
          {expediente?.faseNegocio === 'contratacion' ? (
            <ContratacionGestionPanel
              expedienteId={expedienteId}
              focusPaso={notificacionSearch?.paso}
              abrirRevision={notificacionSearch?.revision === '1'}
              onFocusConsumed={limpiarNotificacionSearch}
            />
          ) : expediente?.faseNegocio === 'requerimientos' ? (
            <RequerimientosGestionPanel
              expedienteId={expedienteId}
              focusDocumentoId={notificacionSearch?.documento}
              abrirRevision={notificacionSearch?.revision === '1'}
              onFocusConsumed={limpiarNotificacionSearch}
            />
          ) : expediente?.faseNegocio === 'tramitacion' ? (
            <TramitacionEnConstruccionPanel
              expedienteId={expedienteId}
              numero={expediente.numero}
            />
          ) : expediente ? (
            <StubTab label="Gestión de Fases" />
          ) : (
            <StubTab label="Gestión de Fases" />
          )}
        </TabsContent>
        <TabsContent value="escritos">
          {expediente?.faseNegocio === 'contratacion' ? (
            <FaseEscritosNoDisponible />
          ) : expediente ? (
            <ExpedienteEscritosPanel expedienteId={expedienteId} />
          ) : (
            <StubTab label="Escritos" />
          )}
        </TabsContent>
        <TabsContent value="documentacion">
          <ExpedienteDocumentacionPanel expedienteId={expedienteId} />
        </TabsContent>
        <TabsContent value="auditoria">
          <ExpedienteAuditoriaPanel
            expedienteId={expedienteId}
            focusHitoId={notificacionSearch?.hito}
            onFocusConsumed={limpiarNotificacionSearch}
          />
        </TabsContent>
        <TabsContent value="facturacion">
          <ExpedienteFacturacionPanel expedienteId={expedienteId} />
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
