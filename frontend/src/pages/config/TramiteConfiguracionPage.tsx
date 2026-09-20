import { useNavigate } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import { FileText, FolderOpen, Shield, UserCheck } from 'lucide-react';
import { api, FASE_DOCUMENTOS_CLIENTE } from '@/api/client';
import { CamposFormularioEditor } from '@/components/config/CamposFormularioEditor';
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
  { value: 'rgpd', tipo: 'rgpd', label: 'RGPD (contrato protección de datos)', icon: Shield },
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

  const { data: docsTramite } = useQuery({
    queryKey: ['documentos-requeridos', tramiteId],
    queryFn: () => api.getDocumentosRequeridos(tramiteId),
    enabled: tab === 'documentacion',
  });

  const { data: docsServicio } = useQuery({
    queryKey: ['documentos-requeridos-servicio', tramite?.servicioId],
    queryFn: () => api.getDocumentosRequeridosServicio(tramite!.servicioId!),
    enabled: tab === 'documentacion' && Boolean(tramite?.servicioId),
  });

  const countFaseCliente = (docs: { fase?: unknown }[] | undefined) =>
    (docs ?? []).filter((d) => {
      const f = d.fase;
      return f === FASE_DOCUMENTOS_CLIENTE || f === 2 || f === 'apertura';
    }).length;

  const nServicio = countFaseCliente(docsServicio?.documentos);
  const nTramite = countFaseCliente(docsTramite?.documentos);
  const nTotal = nServicio + nTramite;

  const handleTabChange = (value: string) => {
    navigate({
      to: '/config/tramites/$tramiteId/configuracion',
      params: { tramiteId },
      search: { tab: value as TramiteConfigTab },
    } as never);
  };

  const escritoTab = ESCRITO_TABS.find((t) => t.value === tab);

  return (
    <div className="flex h-[calc(100dvh-4rem-2.75rem)] flex-col overflow-hidden bg-muted/30">
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
                <div className="rounded-xl border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-foreground">
                  <p className="font-semibold">
                    {nTotal} documento{nTotal === 1 ? '' : 's'} pedirá el cliente en documentación
                  </p>
                  <p className="mt-0.5 text-muted-foreground">
                    {nServicio} heredado{nServicio === 1 ? '' : 's'} del servicio
                    {nServicio > 0 || nTramite > 0 ? ' · ' : ''}
                    {nTramite} específico{nTramite === 1 ? '' : 's'} de este trámite. Ambos grupos se
                    incluyen.
                  </p>
                </div>
                {tramite?.servicioId && (
                  <DocumentosServicioHeredadosPanel servicioId={tramite.servicioId} />
                )}
                <DocumentosRequeridosPanel tramiteId={tramiteId} />
                <CamposFormularioEditor scope="tramite" entityId={tramiteId} />
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

export { TIPOS_ESCRITO };
