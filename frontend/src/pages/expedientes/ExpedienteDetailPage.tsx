import { useEffect, useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import { ExternalLink } from 'lucide-react';
import { api } from '@/api/client';
import type { ExpedienteNotificacionSearch } from '@/lib/notificacion-destino';
import { ContratacionGestionPanel } from '@/components/expedientes/contratacion/ContratacionGestionPanel';
import { DocumentacionGestionPanel } from '@/components/expedientes/documentacion/DocumentacionGestionPanel';
import { TramitacionPanel } from '@/components/expedientes/tramitacion/TramitacionPanel';
import { ResolucionPanel } from '@/components/expedientes/resolucion/ResolucionPanel';
import { ExpedienteEscritosPanel } from '@/components/expedientes/escritos/ExpedienteEscritosPanel';
import { ExpedienteFacturacionPanel } from '@/components/expedientes/ExpedienteFacturacionPanel';
import { ExpedienteDocumentacionPanel } from '@/components/expedientes/ExpedienteDocumentacionPanel';
import { ExpedienteAuditoriaPanel } from '@/components/expedientes/ExpedienteAuditoriaPanel';
import { ExpedienteNotasPanel } from '@/components/expedientes/ExpedienteNotasPanel';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { ExpedienteSubfaseBadge } from '@/components/expedientes/ExpedienteSubfaseBadge';
import { ExpedienteGestionToolbarActions } from '@/components/expedientes/ExpedienteGestionToolbarActions';
import { ExpedienteEstadoActions } from '@/components/expedientes/ExpedienteEstadoActions';
import { ExpedienteConfiguracionModal } from '@/components/expedientes/ExpedienteConfiguracionModal';
import { consumirNotificacionAlta } from '@/lib/email-notificacion';
import { capitalizeDisplay } from '@/lib/capitalize-display';
import { cn } from '@/lib/utils';

interface ExpedienteDetailPageProps {
  expedienteId: string;
  notificacionSearch?: ExpedienteNotificacionSearch;
}

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

  const tabFromNotificacion =
    notificacionSearch?.tab === 'documentacion' ? 'archivo' : notificacionSearch?.tab;
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

  const titulo = capitalizeDisplay(expediente?.titulo) || 'Cargando…';
  const clientNameRaw = expediente?.clientName?.trim() ?? '';
  const clientName =
    clientNameRaw && clientNameRaw !== 'Cliente pendiente'
      ? capitalizeDisplay(clientNameRaw)
      : '';
  const fichaDisponible = Boolean(expediente?.clienteFichaDisponible && expediente.clienteId);

  return (
    <div className="p-6">
      <div className="mb-6">
        <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
          <h1 className="page-title min-w-0">{titulo}</h1>
          {expediente && (
            <div className="ml-auto flex shrink-0 flex-wrap items-center gap-2">
              <ExpedienteConfiguracionModal expediente={expediente} />
              <ExpedienteEstadoActions expediente={expediente} />
            </div>
          )}
        </div>

        {expediente && (
          <div className="mt-2 flex flex-wrap items-center gap-2">
            {fichaDisponible ? (
              <Link
                to="/clientes/$clienteId"
                params={{ clienteId: expediente.clienteId! }}
                state={{ breadcrumb: { keepTrail: true } } as never}
                className="inline-flex items-center gap-1.5 text-base font-semibold text-primary hover:underline"
                title="Abrir ficha del cliente. Si ya hay firmas, revise que los datos coincidan con los documentos firmados."
              >
                {clientName || 'Ver cliente'}
                <ExternalLink className="h-3.5 w-3.5 shrink-0 opacity-70" />
              </Link>
            ) : (
              <p className="text-base font-semibold text-foreground">
                {clientName || 'Pendiente de identificación'}
              </p>
            )}

            {expediente.faseNegocio === 'tramitacion' && expediente.actorBandejaTramitacion && (
              <Badge variant={expediente.actorBandejaTramitacion === 'despacho' ? 'warning' : 'info'}>
                {expediente.actorBandejaTramitacion === 'despacho' ? 'En despacho' : 'En Mercurio'}
              </Badge>
            )}
            {expediente.faseNegocio === 'tramitacion' ? (
              <ExpedienteSubfaseBadge expediente={expediente} />
            ) : null}
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
            <TabsTrigger value="archivo">Archivo</TabsTrigger>
            <TabsTrigger value="notas">Notas</TabsTrigger>
            <TabsTrigger value="auditoria">Auditoría</TabsTrigger>
            <TabsTrigger value="facturacion">Facturación</TabsTrigger>
          </TabsList>
          {activeTab === 'gestion' &&
            expediente?.faseNegocio &&
            expediente.estado === 'abierto' && (
              <ExpedienteGestionToolbarActions
                expedienteId={expedienteId}
                faseNegocio={expediente.faseNegocio}
              />
            )}
        </div>

        {expediente && expediente.estado !== 'abierto' && activeTab === 'gestion' && (
          <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            Este expediente está{' '}
            <strong>{expediente.estadoLabel || expediente.estado}</strong>.
            {expediente.estado === 'cancelado'
              ? ' La gestión de fases queda en solo lectura hasta reabrirlo.'
              : ' El expediente está cerrado; no admite nuevas gestiones.'}
          </div>
        )}

        <TabsContent value="gestion">
          {expediente?.faseNegocio === 'contratacion' ? (
            <ContratacionGestionPanel
              expedienteId={expedienteId}
              subfaseContratacion={expediente.subfaseContratacion}
              focusPaso={notificacionSearch?.paso}
              abrirRevision={notificacionSearch?.revision === '1'}
              onFocusConsumed={limpiarNotificacionSearch}
            />
          ) : expediente?.faseNegocio === 'documentacion' ? (
            <DocumentacionGestionPanel
              expedienteId={expedienteId}
              focusDocumentoId={notificacionSearch?.documento}
              abrirRevision={notificacionSearch?.revision === '1'}
              onFocusConsumed={limpiarNotificacionSearch}
            />
          ) : expediente?.faseNegocio === 'tramitacion' ? (
            <TramitacionPanel expedienteId={expedienteId} />
          ) : expediente?.faseNegocio === 'resolucion' ? (
            <ResolucionPanel expedienteId={expedienteId} numero={expediente.numero} />
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
        <TabsContent value="archivo">
          <ExpedienteDocumentacionPanel expedienteId={expedienteId} />
        </TabsContent>
        <TabsContent value="notas">
          <ExpedienteNotasPanel
            expedienteId={expedienteId}
            enabled={activeTab === 'notas'}
          />
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
      <p className="mt-2 mx-auto max-w-md text-sm text-muted-foreground">
        Los escritos adicionales (documentación, tramitación, resolución) estarán disponibles a
        partir de la fase 2. En contratación solo se generan los documentos legales firmados por el
        cliente.
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
