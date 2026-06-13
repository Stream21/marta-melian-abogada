import { useQuery } from '@tanstack/react-query';
import { api, type ContratacionHitoResponse, type ContratacionPasoResponse } from '@/api/client';
import { Badge } from '@/components/ui/badge';
import { Construction } from 'lucide-react';

interface RequerimientosEnConstruccionPanelProps {
  expedienteId: string;
  numero?: string;
  mostrarResumenContratacion?: boolean;
}

export function RequerimientosEnConstruccionPanel({
  expedienteId,
  numero,
  mostrarResumenContratacion = true,
}: RequerimientosEnConstruccionPanelProps) {
  const { data } = useQuery({
    queryKey: ['contratacion', expedienteId],
    queryFn: () => api.getContratacion(expedienteId),
    enabled: mostrarResumenContratacion,
  });

  return (
    <div className="space-y-6">
      <div className="panel p-10 text-center">
        <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
          <Construction className="h-8 w-8 text-primary" />
        </div>
        <p className="section-label">Fase 2</p>
        <h2 className="mt-1 text-xl font-bold text-foreground">Requerimientos</h2>
        <p className="mx-auto mt-4 max-w-md text-sm leading-relaxed text-muted-foreground">
          Nos encontramos en la fase 2, requerimientos, que se encuentra en desarrollo.
        </p>
        {numero && (
          <p className="mt-2 text-xs text-muted-foreground">Expediente {numero}</p>
        )}
        <Badge variant="info" className="mt-5">
          En desarrollo
        </Badge>
      </div>

      {mostrarResumenContratacion && data && data.hitos.length > 0 && (
        <div className="panel p-6">
          <h3 className="section-label mb-4">Resumen de contratación completada</h3>
          <TimelineReadonly pasos={data.pasos} hitos={data.hitos} />
        </div>
      )}
    </div>
  );
}

function TimelineReadonly({
  pasos,
  hitos,
}: {
  pasos: ContratacionPasoResponse[];
  hitos: ContratacionHitoResponse[];
}) {
  return (
    <div className="space-y-4">
      <div className="flex flex-wrap gap-2">
        {pasos.map((p) => (
          <Badge key={p.paso} variant={p.estado === 'validado_abogado' ? 'success' : 'secondary'}>
            {p.label}
          </Badge>
        ))}
      </div>
      <ul className="space-y-2">
        {hitos.slice(0, 8).map((h) => (
          <li key={h.id} className="text-sm text-muted-foreground">
            {new Date(h.createdAt).toLocaleString('es-ES')} — {h.descripcion}
          </li>
        ))}
      </ul>
    </div>
  );
}
