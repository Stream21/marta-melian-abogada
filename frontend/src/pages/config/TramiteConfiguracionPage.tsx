import { useNavigate } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import { FileText, FolderOpen, Shield, UserCheck } from 'lucide-react';
import { api } from '@/api/client';
import { ConfigBreadcrumb } from '@/components/config/ConfigBreadcrumb';
import { DocumentosRequeridosPanel } from '@/components/config/tramite/DocumentosRequeridosPanel';
import { DocumentosServicioHeredadosPanel } from '@/components/config/tramite/DocumentosServicioHeredadosPanel';
import { TramiteContratacionOtpPanel } from '@/components/config/tramite/TramiteContratacionOtpPanel';
import { EscritoDesigner } from '@/components/hoja-encargo/HojaEncargoDesigner';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { TipoEscrito } from '@/lib/hoja-encargo-variables';
import { TIPOS_ESCRITO } from '@/lib/hoja-encargo-variables';

export type TramiteConfigTab =
  | 'hoja-encargo'
  | 'designacion'
  | 'rgpd'
  | 'documentacion';

const ESCRITO_TABS: Array<{
  value: TramiteConfigTab;
  tipo: TipoEscrito;
  label: string;
  icon: typeof FileText;
}> = [
  { value: 'hoja-encargo', tipo: 'hoja_encargo', label: 'Hoja de encargo', icon: FileText },
  { value: 'designacion', tipo: 'designacion', label: 'Designación', icon: UserCheck },
  { value: 'rgpd', tipo: 'rgpd', label: 'RGPD', icon: Shield },
];

interface TramiteConfiguracionPageProps {
  tramiteId: string;
  tab: TramiteConfigTab;
}

export function TramiteConfiguracionPage({ tramiteId, tab }: TramiteConfiguracionPageProps) {
  const navigate = useNavigate();

  const { data: tramite } = useQuery({
    queryKey: ['tramite', tramiteId],
    queryFn: () => api.getTramite(tramiteId),
  });

  const handleTabChange = (value: string) => {
    navigate({
      to: '/config/tramites/$tramiteId/configuracion',
      params: { tramiteId },
      search: { tab: value as TramiteConfigTab },
    } as never);
  };

  const escritoTab = ESCRITO_TABS.find((t) => t.value === tab);

  return (
    <div className="flex h-[calc(100dvh-4rem)] flex-col overflow-hidden bg-muted/30">
      <ConfigBreadcrumb section="tramites" variant="configuracion" />

      <div className="flex min-h-0 flex-1 flex-col px-6 py-4 md:px-8">
        <div className="mx-auto flex w-full max-w-[1400px] min-h-0 flex-1 flex-col gap-3">
          <TramiteContratacionOtpPanel tramiteId={tramiteId} />
          <Tabs value={tab} onValueChange={handleTabChange} className="shrink-0">
            <TabsList className="h-auto w-full flex-wrap justify-start gap-1 bg-card p-1 shadow-sm ring-1 ring-border">
              {ESCRITO_TABS.map(({ value, label, icon: Icon }) => (
                <TabsTrigger key={value} value={value} className="gap-2 data-[state=active]:text-primary">
                  <Icon className="h-4 w-4" />
                  {label}
                </TabsTrigger>
              ))}
              <TabsTrigger value="documentacion" className="gap-2 data-[state=active]:text-primary">
                <FolderOpen className="h-4 w-4" />
                Documentación requerida
              </TabsTrigger>
            </TabsList>
          </Tabs>

          <div className="min-h-0 flex-1 overflow-hidden">
            {escritoTab && (
              <EscritoDesigner key={escritoTab.value} tramiteId={tramiteId} tipo={escritoTab.tipo} />
            )}
            {tab === 'documentacion' && (
              <div className="h-full overflow-y-auto overscroll-contain space-y-6">
                {tramite?.servicioId && (
                  <DocumentosServicioHeredadosPanel servicioId={tramite.servicioId} />
                )}
                <DocumentosRequeridosPanel tramiteId={tramiteId} />
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

export { TIPOS_ESCRITO };
